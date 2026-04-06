"""
Face Recognition Microservice v2
Using DeepFace with VGG-Face model for face enrollment and verification.
"""

import os
import json
import base64
import io
import logging
from typing import Optional, Dict, Any, List
from datetime import datetime

import numpy as np
from PIL import Image
from fastapi import FastAPI, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

# DeepFace imports
from deepface import DeepFace

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Initialize FastAPI app
app = FastAPI(
    title="Face Recognition Service v2",
    description="Microservice for face enrollment and verification using DeepFace",
    version="2.0.0"
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configuration
DATABASE_DIR = os.path.join(os.path.dirname(__file__), 'database')
os.makedirs(DATABASE_DIR, exist_ok=True)

# DeepFace configuration
MODEL_NAME = "VGG-Face"
DETECTOR_BACKEND = "opencv"
DISTANCE_METRIC = "cosine"
DEFAULT_THRESHOLD = float(os.getenv("FACE_MATCH_THRESHOLD", "0.6"))

# Metadata file for storing employee info
METADATA_FILE = os.path.join(DATABASE_DIR, 'enrollments.json')


# ============================================================================
# Pydantic Models
# ============================================================================

class EnrollRequest(BaseModel):
    employee_id: int = Field(..., description="Unique employee ID")
    image: str = Field(..., description="Base64 encoded image data")


class EnrollResponse(BaseModel):
    success: bool
    employee_id: int
    message: str
    enrolled_at: Optional[str] = None


class VerifyRequest(BaseModel):
    image: str = Field(..., description="Base64 encoded image data")
    threshold: Optional[float] = Field(default=0.6, description="Matching threshold (0.0-1.0)")


class VerifyResponse(BaseModel):
    success: bool
    matched: bool
    employee_id: Optional[int] = None
    confidence: Optional[float] = None
    message: str


class VerifySpecificRequest(BaseModel):
    employee_id: int = Field(..., description="Employee ID to verify against")
    image: str = Field(..., description="Base64 encoded image data")
    threshold: Optional[float] = Field(default=0.6, description="Matching threshold (0.0-1.0)")


class DeleteResponse(BaseModel):
    success: bool
    message: str


class HealthResponse(BaseModel):
    status: str
    service: str
    version: str
    model: str
    detector: str


class EnrolledEmployee(BaseModel):
    employee_id: int
    enrolled_at: str


class EnrolledListResponse(BaseModel):
    success: bool
    count: int
    employees: List[EnrolledEmployee]


# ============================================================================
# Helper Functions
# ============================================================================

def decode_base64_image(base64_string: str) -> np.ndarray:
    """Decode base64 image string to numpy array."""
    try:
        # Remove data URL prefix if present
        if "," in base64_string:
            base64_string = base64_string.split(",")[1]
        
        image_bytes = base64.b64decode(base64_string)
        image = Image.open(io.BytesIO(image_bytes))
        
        # Convert to RGB if necessary
        if image.mode != 'RGB':
            image = image.convert('RGB')
        
        return np.array(image)
    except Exception as e:
        logger.error(f"Failed to decode image: {e}")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Invalid image data: {str(e)}"
        )


def load_metadata() -> Dict[str, Any]:
    """Load enrollment metadata from JSON file."""
    if os.path.exists(METADATA_FILE):
        try:
            with open(METADATA_FILE, 'r') as f:
                return json.load(f)
        except Exception as e:
            logger.error(f"Failed to load metadata: {e}")
    return {"enrollments": {}}


def save_metadata(metadata: Dict[str, Any]):
    """Save enrollment metadata to JSON file."""
    try:
        with open(METADATA_FILE, 'w') as f:
            json.dump(metadata, f, indent=2)
    except Exception as e:
        logger.error(f"Failed to save metadata: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to save enrollment data: {str(e)}"
        )


def get_employee_image_path(employee_id: int) -> str:
    """Get file path for employee's enrolled face image."""
    return os.path.join(DATABASE_DIR, f"employee_{employee_id}.jpg")


# ============================================================================
# API Endpoints
# ============================================================================

@app.get("/", response_model=Dict[str, str])
async def root():
    """Root endpoint - service information."""
    return {
        "service": "Face Recognition Service v2",
        "version": "2.0.0",
        "model": MODEL_NAME,
        "detector": DETECTOR_BACKEND,
        "docs": "/docs"
    }


@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint."""
    return HealthResponse(
        status="healthy",
        service="face-recognition-v2",
        version="2.0.0",
        model=MODEL_NAME,
        detector=DETECTOR_BACKEND
    )


@app.post("/enroll", response_model=EnrollResponse)
async def enroll(request: EnrollRequest):
    """
    Enroll a new face for an employee.
    Saves the face image and creates metadata entry.
    """
    try:
        logger.info(f"Enrolling face for employee {request.employee_id}")
        
        # Decode image
        image_array = decode_base64_image(request.image)
        
        # Save image to database directory
        image_path = get_employee_image_path(request.employee_id)
        
        # Convert numpy array to PIL Image and save
        pil_image = Image.fromarray(image_array)
        pil_image.save(image_path, quality=95)
        
        # Verify that DeepFace can detect a face in the image
        try:
            embedding = DeepFace.represent(
                img_path=image_path,
                model_name=MODEL_NAME,
                detector_backend=DETECTOR_BACKEND,
                enforce_detection=True
            )
            logger.info(f"Face detected and embedding extracted for employee {request.employee_id}")
        except Exception as e:
            # Remove saved image if face detection fails
            if os.path.exists(image_path):
                os.remove(image_path)
            logger.error(f"No face detected in enrollment image: {e}")
            return EnrollResponse(
                success=False,
                employee_id=request.employee_id,
                message="No face detected in image. Please ensure your face is clearly visible and well-lit."
            )
        
        # Load and update metadata
        metadata = load_metadata()
        enrolled_at = datetime.now().isoformat()
        
        metadata["enrollments"][str(request.employee_id)] = {
            "enrolled_at": enrolled_at,
            "image_path": image_path,
            "model": MODEL_NAME
        }
        
        save_metadata(metadata)
        
        logger.info(f"Successfully enrolled employee {request.employee_id}")
        
        return EnrollResponse(
            success=True,
            employee_id=request.employee_id,
            message="Face enrolled successfully",
            enrolled_at=enrolled_at
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Enrollment failed: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Enrollment failed: {str(e)}"
        )


@app.post("/verify", response_model=VerifyResponse)
async def verify(request: VerifyRequest):
    """
    Verify a face against all enrolled employees.
    Uses DeepFace.find() to search for the best match.
    """
    try:
        logger.info("Verifying face against all enrolled employees")
        
        # Check if any employees are enrolled
        metadata = load_metadata()
        if not metadata["enrollments"]:
            return VerifyResponse(
                success=True,
                matched=False,
                message="No enrolled employees found. Please enroll first."
            )
        
        # Decode image
        image_array = decode_base64_image(request.image)
        
        # Save temporary image for DeepFace processing
        temp_path = os.path.join(DATABASE_DIR, "temp_verify.jpg")
        pil_image = Image.fromarray(image_array)
        pil_image.save(temp_path, quality=95)
        
        try:
            # Use DeepFace.find() to search across all enrolled faces
            results = DeepFace.find(
                img_path=temp_path,
                db_path=DATABASE_DIR,
                model_name=MODEL_NAME,
                detector_backend=DETECTOR_BACKEND,
                distance_metric=DISTANCE_METRIC,
                enforce_detection=True,
                silent=True
            )
            
            # Clean up temp file
            if os.path.exists(temp_path):
                os.remove(temp_path)
            
            # Check if any match found
            if results and len(results) > 0 and len(results[0]) > 0:
                # Get the best match
                best_match = results[0].iloc[0]
                distance = best_match['distance']
                
                # Convert distance to confidence (cosine distance to similarity)
                confidence = 1 - distance
                
                # Extract employee_id from the matched image filename
                matched_path = best_match['identity']
                employee_id = None
                
                # Parse employee ID from filename (employee_{id}.jpg)
                filename = os.path.basename(matched_path)
                if filename.startswith("employee_") and filename.endswith(".jpg"):
                    try:
                        employee_id = int(filename.replace("employee_", "").replace(".jpg", ""))
                    except ValueError:
                        pass
                
                # Check if confidence meets threshold
                if confidence >= request.threshold and employee_id is not None:
                    logger.info(f"Face matched with employee {employee_id} (confidence: {confidence:.3f})")
                    
                    return VerifyResponse(
                        success=True,
                        matched=True,
                        employee_id=employee_id,
                        confidence=round(confidence, 3),
                        message="Face matched successfully"
                    )
                else:
                    logger.info(f"Match found but below threshold: {confidence:.3f} < {request.threshold}")
                    return VerifyResponse(
                        success=True,
                        matched=False,
                        message=f"Face found but confidence too low ({confidence:.2f}). Please try again."
                    )
            else:
                logger.info("No matching face found")
                return VerifyResponse(
                    success=True,
                    matched=False,
                    message="Face not recognized. Please try again or contact HR."
                )
                
        except Exception as e:
            # Clean up temp file on error
            if os.path.exists(temp_path):
                os.remove(temp_path)
            
            # Check if error is due to no face detected
            error_msg = str(e).lower()
            if "face" in error_msg and ("not" in error_msg or "no" in error_msg or "detect" in error_msg):
                return VerifyResponse(
                    success=True,
                    matched=False,
                    message="No face detected in image. Please ensure your face is clearly visible."
                )
            
            raise e
            
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Verification failed: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Verification failed: {str(e)}"
        )


@app.post("/verify-specific", response_model=VerifyResponse)
async def verify_specific(request: VerifySpecificRequest):
    """
    Verify a face against a specific employee.
    Compares captured image directly with stored employee image.
    """
    try:
        logger.info(f"Verifying face against specific employee {request.employee_id}")
        
        # Check if employee is enrolled
        metadata = load_metadata()
        employee_str = str(request.employee_id)
        
        if employee_str not in metadata["enrollments"]:
            return VerifyResponse(
                success=True,
                matched=False,
                message=f"Employee {request.employee_id} not enrolled."
            )
        
        # Get stored image path
        stored_image_path = metadata["enrollments"][employee_str]["image_path"]
        
        if not os.path.exists(stored_image_path):
            return VerifyResponse(
                success=True,
                matched=False,
                message=f"Stored face image not found for employee {request.employee_id}."
            )
        
        # Decode captured image
        image_array = decode_base64_image(request.image)
        
        # Save temporary image
        temp_path = os.path.join(DATABASE_DIR, "temp_verify_specific.jpg")
        pil_image = Image.fromarray(image_array)
        pil_image.save(temp_path, quality=95)
        
        try:
            # Use DeepFace.verify() to compare two images
            result = DeepFace.verify(
                img1_path=temp_path,
                img2_path=stored_image_path,
                model_name=MODEL_NAME,
                detector_backend=DETECTOR_BACKEND,
                distance_metric=DISTANCE_METRIC,
                enforce_detection=True
            )
            
            # Clean up temp file
            if os.path.exists(temp_path):
                os.remove(temp_path)
            
            # Extract results
            verified = result.get('verified', False)
            distance = result.get('distance', 1.0)
            confidence = 1 - distance  # Convert distance to confidence
            
            if verified and confidence >= request.threshold:
                logger.info(f"Face verified for employee {request.employee_id} (confidence: {confidence:.3f})")
                
                return VerifyResponse(
                    success=True,
                    matched=True,
                    employee_id=request.employee_id,
                    confidence=round(confidence, 3),
                    message="Face verified successfully"
                )
            else:
                logger.info(f"Face verification failed for employee {request.employee_id} (confidence: {confidence:.3f})")
                
                return VerifyResponse(
                    success=True,
                    matched=False,
                    employee_id=request.employee_id,
                    confidence=round(confidence, 3),
                    message="Face does not match the specified employee."
                )
                
        except Exception as e:
            # Clean up temp file on error
            if os.path.exists(temp_path):
                os.remove(temp_path)
            
            # Check if error is due to no face detected
            error_msg = str(e).lower()
            if "face" in error_msg and ("not" in error_msg or "no" in error_msg or "detect" in error_msg):
                return VerifyResponse(
                    success=True,
                    matched=False,
                    message="No face detected in image. Please ensure your face is clearly visible."
                )
            
            raise e
            
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Specific verification failed: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Verification failed: {str(e)}"
        )


@app.get("/enrolled-employees", response_model=EnrolledListResponse)
async def get_enrolled_employees():
    """Get list of all enrolled employees."""
    try:
        metadata = load_metadata()
        enrollments = metadata.get("enrollments", {})
        
        employees = []
        for emp_id, data in enrollments.items():
            employees.append(EnrolledEmployee(
                employee_id=int(emp_id),
                enrolled_at=data.get("enrolled_at", "")
            ))
        
        return EnrolledListResponse(
            success=True,
            count=len(employees),
            employees=employees
        )
        
    except Exception as e:
        logger.error(f"Failed to get enrolled employees: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to retrieve enrolled employees: {str(e)}"
        )


@app.delete("/enroll/{employee_id}", response_model=DeleteResponse)
async def delete_enrollment(employee_id: int):
    """Remove face enrollment for an employee."""
    try:
        logger.info(f"Removing enrollment for employee {employee_id}")
        
        # Load metadata
        metadata = load_metadata()
        employee_str = str(employee_id)
        
        if employee_str not in metadata["enrollments"]:
            return DeleteResponse(
                success=False,
                message=f"Employee {employee_id} is not enrolled."
            )
        
        # Get image path and delete file
        image_path = metadata["enrollments"][employee_str].get("image_path")
        if image_path and os.path.exists(image_path):
            os.remove(image_path)
            logger.info(f"Deleted image file: {image_path}")
        
        # Remove from metadata
        del metadata["enrollments"][employee_str]
        save_metadata(metadata)
        
        logger.info(f"Successfully removed enrollment for employee {employee_id}")
        
        return DeleteResponse(
            success=True,
            message=f"Enrollment removed for employee {employee_id}"
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Failed to remove enrollment: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to remove enrollment: {str(e)}"
        )


# ============================================================================
# Main Entry Point
# ============================================================================

if __name__ == "__main__":
    import uvicorn
    
    port = int(os.getenv("PORT", "5000"))
    host = os.getenv("HOST", "0.0.0.0")
    
    logger.info(f"Starting Face Recognition Service v2 on {host}:{port}")
    logger.info(f"Model: {MODEL_NAME}, Detector: {DETECTOR_BACKEND}")
    logger.info(f"Database directory: {DATABASE_DIR}")
    
    uvicorn.run(app, host=host, port=port)

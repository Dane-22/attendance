# Face Recognition Service v2 - Documentation

## Overview

A completely isolated microservice for face enrollment and verification using **DeepFace** with **VGG-Face** model. This service provides REST API endpoints for storing face embeddings and matching captured faces against enrolled employees.

**Key Differences from v1:**
- Uses DeepFace library (state-of-the-art face recognition)
- VGG-Face model for superior accuracy
- Stores actual face images (not just embeddings)
- Simplified architecture with JSON metadata

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Framework | FastAPI |
| Face Recognition | DeepFace (VGG-Face model) |
| Face Detection | OpenCV |
| Distance Metric | Cosine Similarity |
| Deployment | Docker + Docker Compose |

---

## Project Structure

```
face-recognition-v2/
├── main.py                    # FastAPI application
├── requirements.txt           # Python dependencies
├── Dockerfile                 # Container definition
├── docker-compose.yml         # Orchestration config
├── database/                  # Persisted storage
│   ├── .gitkeep              # Directory placeholder
│   ├── enrollments.json      # Metadata (auto-created)
│   └── employee_{id}.jpg     # Enrolled face images
└── DOCUMENTATION.md         # This file
```

---

## API Endpoints

### 1. Health Check
```http
GET /health
```

**Response:**
```json
{
  "status": "healthy",
  "service": "face-recognition-v2",
  "version": "2.0.0",
  "model": "VGG-Face",
  "detector": "opencv"
}
```

---

### 2. Enroll Face
Store face image for an employee.

```http
POST /enroll
Content-Type: application/json

{
  "employee_id": 123,
  "image": "base64_encoded_image_data"
}
```

**Success Response:**
```json
{
  "success": true,
  "employee_id": 123,
  "message": "Face enrolled successfully",
  "enrolled_at": "2026-04-01T12:00:00"
}
```

**Error Response:**
```json
{
  "success": false,
  "employee_id": 123,
  "message": "No face detected in image. Please ensure your face is clearly visible and well-lit."
}
```

---

### 3. Verify Face (Against All)
Match captured face against all enrolled employees.

```http
POST /verify
Content-Type: application/json

{
  "image": "base64_encoded_image_data",
  "threshold": 0.6
}
```

**Match Found:**
```json
{
  "success": true,
  "matched": true,
  "employee_id": 123,
  "confidence": 0.85,
  "message": "Face matched successfully"
}
```

**No Match:**
```json
{
  "success": true,
  "matched": false,
  "message": "Face not recognized. Please try again or contact HR."
}
```

---

### 4. Verify Specific Employee
Match face against a specific employee.

```http
POST /verify-specific
Content-Type: application/json

{
  "employee_id": 123,
  "image": "base64_encoded_image_data",
  "threshold": 0.6
}
```

---

### 5. List Enrolled Employees
```http
GET /enrolled-employees
```

**Response:**
```json
{
  "success": true,
  "count": 5,
  "employees": [
    {
      "employee_id": 123,
      "enrolled_at": "2026-04-01T12:00:00"
    }
  ]
}
```

---

### 6. Delete Enrollment
```http
DELETE /enroll/{employee_id}
```

**Response:**
```json
{
  "success": true,
  "message": "Enrollment removed for employee 123"
}
```

---

## Setup & Deployment

### Option 1: Docker Compose (Recommended)

```bash
# Navigate to service directory
cd face-recognition-v2

# Build and start
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f

# Stop service
docker-compose down
```

**Access:**
- API: http://localhost:5000
- Swagger UI: http://localhost:5000/docs
- ReDoc: http://localhost:5000/redoc

---

### Option 2: Local Development

```bash
# Create virtual environment
python3 -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Run service
python main.py
```

**Note:** First run will download VGG-Face model (~500MB).

---

## Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `PORT` | 5000 | API server port |
| `HOST` | 0.0.0.0 | Bind address |
| `FACE_MATCH_THRESHOLD` | 0.6 | Minimum confidence (0.0-1.0) |

### Docker Compose Settings

```yaml
services:
  face-recognition-v2:
    ports:
      - "5000:5000"    # Map host:container
    volumes:
      - ./database:/app/database  # Persist enrolled faces
    environment:
      - FACE_MATCH_THRESHOLD=0.6
```

---

## How It Works

### Enrollment Flow

1. **Receive** base64 image + employee_id
2. **Decode** image to numpy array
3. **Save** image to `database/employee_{id}.jpg`
4. **Extract** embedding using DeepFace.represent()
5. **Verify** face is detectable
6. **Store** metadata in `enrollments.json`

### Verification Flow

1. **Receive** base64 image
2. **Save** temporary image
3. **Search** using DeepFace.find() across all enrolled images
4. **Compare** using cosine similarity
5. **Return** best match if above threshold
6. **Cleanup** temporary file

---

## Face Recognition Model

### VGG-Face

- **Architecture**: VGG-16 CNN
- **Training**: 2.6M images, 2,622 identities
- **Embedding**: 4,096 dimensions
- **Accuracy**: 98.87% on LFW benchmark

### Why VGG-Face?

- ✅ High accuracy on diverse faces
- ✅ Mature, well-tested model
- ✅ Good balance of speed vs accuracy
- ✅ Works well with Asian faces

### Detection Backend

- **OpenCV Haar Cascade**: Fast, lightweight
- **Alternative**: MTCNN, RetinaFace (more accurate, slower)

---

## Data Storage

### File Structure

```
database/
├── enrollments.json          # Employee metadata
├── employee_123.jpg         # Stored face image
├── employee_456.jpg
└── temp_verify.jpg          # Temporary (auto-deleted)
```

### Metadata Format

```json
{
  "enrollments": {
    "123": {
      "enrolled_at": "2026-04-01T12:00:00",
      "image_path": "/app/database/employee_123.jpg",
      "model": "VGG-Face"
    }
  }
}
```

---

## Security Considerations

### Data Protection

1. **Image Storage**: Raw face images stored (not just embeddings)
   - Consider encryption at rest for production
2. **HTTPS**: Always use TLS in production
3. **Access Control**: Implement API key authentication
4. **Rate Limiting**: Prevent brute force verification

### Privacy

- Store only necessary images
- Implement retention policy (auto-delete after X days)
- GDPR compliance: Right to be forgotten (DELETE endpoint)

---

## Performance

### Metrics

| Operation | Time |
|-----------|------|
| First startup (model download) | ~2-3 minutes |
| Face enrollment | ~500ms |
| Face verification (1:N) | ~1-2 seconds |
| Face verification (1:1) | ~500ms |

### Resource Usage

- **Memory**: ~2GB (VGG-Face model)
- **Disk**: ~500MB (model) + ~100KB per enrolled face
- **CPU**: Moderate during inference

### Scaling

- **Horizontal**: Run multiple containers behind load balancer
- **GPU**: Not required (CPU inference is fast enough)
- **Caching**: Embeddings computed on-demand

---

## Integration with PHP Backend

### Example PHP API Wrapper

```php
<?php
class FaceRecognitionClient {
    private $baseUrl = 'http://localhost:5000';
    
    public function enroll(int $employeeId, string $base64Image): array {
        $response = $this->post('/enroll', [
            'employee_id' => $employeeId,
            'image' => $base64Image
        ]);
        return json_decode($response, true);
    }
    
    public function verify(string $base64Image, float $threshold = 0.6): array {
        $response = $this->post('/verify', [
            'image' => $base64Image,
            'threshold' => $threshold
        ]);
        return json_decode($response, true);
    }
    
    private function post(string $endpoint, array $data): string {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
```

---

## Testing

### Manual API Test

```bash
# 1. Health check
curl http://localhost:5000/health

# 2. Enroll face (requires base64 image)
curl -X POST http://localhost:5000/enroll \
  -H "Content-Type: application/json" \
  -d '{"employee_id": 1, "image": "data:image/jpeg;base64,/9j/4AAQ..."}'

# 3. Verify face
curl -X POST http://localhost:5000/verify \
  -H "Content-Type: application/json" \
  -d '{"image": "data:image/jpeg;base64,/9j/4AAQ...", "threshold": 0.6}'

# 4. List enrolled
curl http://localhost:5000/enrolled-employees

# 5. Delete enrollment
curl -X DELETE http://localhost:5000/enroll/1
```

### Python Test Script

```python
import requests
import base64

# Load image
with open("face.jpg", "rb") as f:
    img_b64 = base64.b64encode(f.read()).decode()

# Enroll
r = requests.post("http://localhost:5000/enroll", json={
    "employee_id": 123,
    "image": f"data:image/jpeg;base64,{img_b64}"
})
print(r.json())

# Verify
r = requests.post("http://localhost:5000/verify", json={
    "image": f"data:image/jpeg;base64,{img_b64}",
    "threshold": 0.6
})
print(r.json())
```

---

## Troubleshooting

### Issue: Model download fails

**Solution:** Check internet connection. First run downloads ~500MB VGG-Face weights.

### Issue: "No face detected"

**Solutions:**
- Ensure good lighting
- Face should fill 30%+ of frame
- Remove masks/sunglasses
- Try closer to camera

### Issue: Low confidence matches

**Solutions:**
- Lower threshold (e.g., 0.5 instead of 0.6)
- Re-enroll with better lighting
- Ensure similar pose to enrollment photo

### Issue: Docker container exits immediately

**Check logs:**
```bash
docker-compose logs face-recognition-v2
```

**Common fixes:**
```bash
# Rebuild
docker-compose down
docker-compose up --build
```

---

## Migration from v1

### Key Differences

| Feature | v1 (OpenCV) | v2 (DeepFace) |
|---------|-------------|---------------|
| Algorithm | Histogram + LBP | VGG-Face CNN |
| Storage | JSON embeddings | JPG images |
| Accuracy | ~85% | ~98% |
| Speed | Fast (<100ms) | Moderate (~1s) |
| Dependencies | Lightweight | Heavy (TensorFlow) |

### Migration Path

1. **Parallel deployment**: Run both services
2. **Re-enroll**: Employees must re-enroll in v2
3. **Switch**: Update PHP backend to call v2 endpoints
4. **Decommission**: Remove v1 after validation

---

## Future Enhancements

1. **Anti-spoofing**: Liveness detection (blink, smile)
2. **Multi-face**: Support for group photos
3. **Gender/Age**: Additional attributes from DeepFace
4. **Emotion**: Detect facial expressions
5. **GPU Support**: CUDA acceleration for large-scale

---

## References

- [DeepFace Documentation](https://github.com/serengil/deepface)
- [VGG-Face Paper](https://www.robots.ox.ac.uk/~vgg/publications/2015/Parkhi15/parkhi15.pdf)
- [FastAPI Documentation](https://fastapi.tiangolo.com/)

---

*Generated on April 1, 2026*

// employee/employee.js
// Per-page helpers for employee/tasks.php
// Adds New Task modal wiring, creates task cards, and basic drag/drop logic.

document.addEventListener('DOMContentLoaded', () => {
	const newTaskBtn = document.getElementById('newTaskBtn');
	const newTaskModal = document.getElementById('newTaskModal');
	const cancelTaskBtn = document.getElementById('cancelTaskBtn');
	const newTaskForm = document.getElementById('newTaskForm');
	const colTodo = document.getElementById('col-todo');
	const lists = Array.from(document.querySelectorAll('.task-list'));

	// Sidebar toggle handling (desktop collapse / mobile overlay)
	const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
	const sidebarToggle = document.getElementById('sidebarToggle');

	function applySidebarState(collapsed){
		if (!sidebar) return;
		if (collapsed) {
			sidebar.classList.add('collapsed');
			sidebar.classList.remove('open');
		} else {
			sidebar.classList.remove('collapsed');
			sidebar.classList.remove('open');
		}
	}

	function toggleSidebar(){
		if (!sidebar) return;
		if (window.innerWidth <= 1000){
			// mobile: toggle overlay
			sidebar.classList.toggle('open');
		} else {
			// desktop: toggle collapsed state
			sidebar.classList.toggle('collapsed');
			const collapsed = sidebar.classList.contains('collapsed');
			localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
		}
	}

	// initialize from saved state
	try{
		const saved = localStorage.getItem('sidebarCollapsed');
		if (saved === '1') applySidebarState(true);
	}catch(e){/* ignore storage errors */}

	if (sidebarToggle) sidebarToggle.addEventListener('click', (e)=>{ e.preventDefault(); toggleSidebar(); });

	// Close overlay when clicking outside on small screens
	document.addEventListener('click', (e)=>{
		if (!sidebar) return;
		if (window.innerWidth > 1000) return;
		if (!sidebar.classList.contains('open')) return;
		if (e.target.closest('.sidebar') || e.target.closest('#sidebarToggle')) return;
		sidebar.classList.remove('open');
	});

	function openModal() {
		if (newTaskModal) newTaskModal.style.display = 'flex';
	}
	function closeModal() {
		if (newTaskModal) newTaskModal.style.display = 'none';
		if (newTaskForm) newTaskForm.reset();
	}

	if (newTaskBtn) newTaskBtn.addEventListener('click', openModal);
	if (cancelTaskBtn) cancelTaskBtn.addEventListener('click', closeModal);

	// Create a task card DOM node
	function createTaskCard({id, title, desc, priority, due}){
		const card = document.createElement('div');
		card.className = 'task-card';
		card.setAttribute('draggable','true');
		if (id) card.dataset.id = id;
		if (title) card.dataset.title = title;
		if (priority) card.dataset.priority = priority;
		if (due) card.dataset.due = due;

		const pr = document.createElement('div');
		pr.className = 'priority-' + (priority || 'Medium');
		pr.textContent = priority || 'Medium';

		const descEl = document.createElement('div');
		descEl.className = 'desc';
		descEl.textContent = title || 'New Task';

		if (desc) {
			const p = document.createElement('div');
			p.className = 'task-desc';
			p.style.fontSize = '0.9rem';
			p.style.marginTop = '6px';
			p.style.color = '#374151';
			p.textContent = desc;
			card.appendChild(pr);
			card.appendChild(descEl);
			card.appendChild(p);
		} else {
			card.appendChild(pr);
			card.appendChild(descEl);
		}

		const meta = document.createElement('div');
		meta.className = 'task-meta';
		meta.textContent = 'Due: ' + (due || 'No due');
		card.appendChild(meta);

		makeDraggable(card);
		return card;
	}

	// Drag handlers
	function makeDraggable(card){
		card.addEventListener('dragstart', (e)=>{
			e.dataTransfer.setData('text/plain', card.dataset.id || card.dataset.title || '');
			e.dataTransfer.effectAllowed = 'move';
			card.classList.add('dragging');
			// store a pointer to the element being dragged
			window._draggedCard = card;
		});
		card.addEventListener('dragend', ()=>{
			card.classList.remove('dragging');
			window._draggedCard = null;
		});
	}

	lists.forEach(list => {
		list.addEventListener('dragover', (e)=>{
			e.preventDefault();
			e.dataTransfer.dropEffect = 'move';
		});
		list.addEventListener('drop', (e)=>{
			e.preventDefault();
			const targetColumn = list.closest('.column')?.dataset?.column || '';
			const dragged = window._draggedCard;
			if (!dragged) return;
			// If dropping into Done, enforce Time In check
			if (targetColumn === 'done'){
				const clockState = localStorage.getItem('clockState') || 'out';
				if (clockState !== 'in'){
					alert('You must be Time In to move tasks to Done.');
					return;
				}
			}
			list.appendChild(dragged);
		});
	});

	// Wire form submit to create a new card in To Do
	if (newTaskForm){
		newTaskForm.addEventListener('submit', (e)=>{
			e.preventDefault();
			const title = document.getElementById('taskTitle')?.value?.trim();
			const desc = document.getElementById('taskDesc')?.value?.trim();
			const priority = document.getElementById('taskPriority')?.value || 'Medium';
			const due = document.getElementById('taskDue')?.value || '';
			if (!title) return alert('Please provide a task title.');

			const id = 'tmp-' + Date.now();
			const card = createTaskCard({id, title, desc, priority, due});
			if (colTodo) colTodo.prepend(card);
			closeModal();
		});
	}

	// Initialize existing cards to be draggable
	document.querySelectorAll('.task-card').forEach(c => makeDraggable(c));
});

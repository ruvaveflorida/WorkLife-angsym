import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { TaskService } from '../../services/task';
import { UserService } from '../../services/user';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-task-edit',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './task-edit-component.html',
  styleUrls: ['./task-edit-component.css']
})
export class TaskEditComponent implements OnInit {
  public task: any = null;
  public loading: boolean = false;
  public saving: boolean = false;
  public users: any[] = [];
  public currentUser: any = null;

  constructor(
    private route: ActivatedRoute,
    private taskService: TaskService,
    private userService: UserService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.currentUser = this.userService.getIdentity();
    const taskId = this.route.snapshot.paramMap.get('id');
    if (taskId) {
      this.loadTask(taskId);
    }
    this.loadUsers();
  }

  loadTask(id: string) {
    this.loading = true;
    this.taskService.getOneTask(id).subscribe({
      next: res => {
        this.task = res.data;
        this.task.assigned_to_id = this.task.assigned_to?.id ?? null;
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: err => {
        console.error('Error al cargar task', err);
        this.loading = false;
      }
    });
  }

  loadUsers() {
    if (this.currentUser.role === 'admin') {
      this.userService.getAllUsers().subscribe({
        next: (res: any) => this.users = res.data,
        error: err => console.error('Error cargando usuarios', err)
      });
    }
  }

  saveTask() {
    if (!this.task) return;

    this.saving = true;

    const payload: any = {
      status: this.task.status
    };

    if (this.currentUser.role === 'admin') {
      payload.title = this.task.title;
      payload.description = this.task.description;
      payload.assigned_to = this.task.assigned_to_id;
    }

    this.taskService.updateTask(this.task.id, payload).subscribe({
      next: res => {
        this.saving = false;
        this.router.navigate(['/task', this.task.id]); 
      },
      error: err => {
        console.error('Error al guardar task', err);
        this.saving = false;
      }
    });
  }

  goBack() {
    this.router.navigate(['/task', this.task?.id]);
  }
}



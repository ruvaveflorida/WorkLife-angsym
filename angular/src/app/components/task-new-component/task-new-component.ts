import { ChangeDetectorRef, Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TaskService } from '../../services/task';
import { UserService } from '../../services/user';
import { Router } from '@angular/router';

@Component({
  selector: 'app-task-new',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './task-new-component.html',
  styleUrl: './task-new-component.css'
})
export class TaskNewComponent {

  public task = {
    title: '',
    description: '',
    status: '',
    assigned_to: null,
  };

  public message = '';
  public messageType: 'success' | 'error' | '' = '';

  public users: any[] = [];

  constructor(
    private taskService: TaskService,
    private userService: UserService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.loadUsers();
  }

  loadUsers() {
    this.userService.getAllUsers().subscribe({
      next: (res: any) => this.users = res.data,
      error: (err) => console.error('Error cargando usuarios', err)
    });
  }

  onSubmit(): void {
    this.message = '';
    this.messageType = '';

    console.log('Token actual:', this.userService.getToken());

    if (!this.task.title || !this.task.status) {
      this.message = 'El título y el estado son obligatorios';
      this.messageType = 'error';
      console.log('Formulario inválido:', this.task);
      return;
    }
    
    console.log('Enviando tarea al backend:', this.task);

    this.taskService.createTask(this.task).subscribe({
      next: (res) => {
        console.log('Respuesta del backend:', res);
        this.message = 'Tarea creada correctamente';
        this.messageType = 'success';

        this.task = {
          title: '',
          description: '',
          status: '',
          assigned_to: null
        };

        setTimeout(() => {
          this.router.navigate(['/']);
        }, 1500);
      },
      error: (err) => {
        console.error('Error al crear la tarea:', err);
        this.message = 'Error al crear la tarea';
        this.messageType = 'error';
      }
    });
  }
}




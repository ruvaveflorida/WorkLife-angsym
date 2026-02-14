import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TaskService } from '../../services/task';
import { Router } from '@angular/router';
import { UserService } from '../../services/user';
import { RouterModule } from '@angular/router';
import { Subject } from 'rxjs';
import { debounceTime } from 'rxjs';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'default',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './default-component.html',
  styleUrl: './default-component.css'
})
export class DefaultComponent implements OnInit {

  public tasks: any[] = [];
  public page: number = 1;
  public totalPages: number = 0;
  public loading: boolean = false;

  public filter: string = 'all';
  public order: string = '1';
  public searchTerm: string = '';
  public assignedToId: number = 0;

  private searchSubject = new Subject<void>();

  public userRole: string = '';
  public users: any[] = [];

  constructor(
    private taskService: TaskService,
    private router: Router,
    private userService: UserService,
    private cdr: ChangeDetectorRef
    
  ) {}

  ngOnInit() {
    const token = this.userService.getToken();
    const identity = this.userService.getIdentity();
    console.log('IDENTITY AL INICIAR DEFAULT-COMPONENT:', identity);

    if (!token) {
      this.router.navigate(['/login']);
      return;
    }
      if (identity) {
        this.userRole = identity.role;
    }
      
    if (this.userRole === 'admin') {
    this.userService.getAllUsers().subscribe({
      next: (res: any) => {
        if (res.status === 'success') {
          this.users = res.data;
        }
      },
      error: (err) => {
        console.error('No se pudieron cargar los usuarios', err);
      }
    });
  }

    this.searchSubject.pipe(debounceTime(300)).subscribe(() => {
      this.page = 1; 
      this.loadTasks();
    });

    this.loadTasks();
  }

  getFilterValue(): number {
    switch (this.filter) {
      case 'new': return 1;
      case 'finished': return 3;
      case 'in_progress': return 4;
      default: return 2; 
    }
  }

  getOrderValue(): number {
    return this.order === '1' ? 1 : 2;
  }

  searchTasks() {
    this.searchSubject.next();
  }

  loadTasks() {
    this.loading = true;

    this.taskService.searchTasks(
      this.searchTerm,
      this.getFilterValue(),
      this.getOrderValue(),
      this.assignedToId
    ).subscribe({
      next: (response: any) => {
        console.log('Respuesta del backend:', response);

        if (response.status === 'success') {
          this.tasks = response.data;
          console.log('Tasks cargadas:', this.tasks);
        }

        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Error al cargar tareas', err);
        this.loading = false;
      }
    });
  }

  deleteTask(id: number) {
    if (!confirm('¿Estás segura de que quieres eliminar esta tarea?')) return;

    this.taskService.deleteTask(id).subscribe({
      next: (response: any) => {
        if (response.status === 'success') {

          this.tasks = this.tasks.filter(task => task.id !== id);
          alert('Tarea eliminada correctamente');

          this.loadTasks();
        }
      },
      error: (err) => {
        console.error('Error al eliminar tarea', err);
        alert('No se pudo eliminar la tarea');
      }
    });
  }

  nextPage() {
    if (this.page < this.totalPages) {
      this.page++;
      this.loadTasks();
    }
  }

  prevPage() {
    if (this.page > 1) {
      this.page--;
      this.loadTasks();
    }
  }
}


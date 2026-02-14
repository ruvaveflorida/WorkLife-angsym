import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { TaskService } from '../../services/task';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-task-detail',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './task-detail-component.html',
  styleUrls: ['./task-detail-component.css']
})
export class TaskDetailComponent implements OnInit {
  public task: any = null;
  public loading: boolean = false;

  constructor(
    private route: ActivatedRoute,
    private taskService: TaskService,
    private cdr: ChangeDetectorRef,
    private router: Router
  ) {}

  ngOnInit(): void {
    const taskId = this.route.snapshot.paramMap.get('id');
    if (taskId) {
      this.loadTask(taskId);
    }
  }

  loadTask(id: string) {
    this.loading = true;
    this.taskService.getOneTask(id).subscribe({
      next: (res) => {
        this.task = res.data;
        // Mapear assigned_to si existe
        this.task.assigned_to_name = this.task.assigned_to 
          ? `${this.task.assigned_to.name} ${this.task.assigned_to.surname} (${this.task.assigned_to.email})`
          : 'No asignado';
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Error al cargar tarea', err);
        this.loading = false;
      }
    });
  }

  goHome() {
    this.router.navigate(['/']);
  }
}


import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { firstValueFrom } from 'rxjs';
import { UserService } from '../../services/user';
import { Router } from '@angular/router';
import { Component, ChangeDetectorRef } from '@angular/core';

@Component({
  selector: 'register',
  imports: [FormsModule, CommonModule],
  templateUrl: './register-component.html',
  styleUrl: './register-component.css',
  standalone: true
})
export class RegisterComponent {
  public user: { name: string; surname: string; email: string; password: string } = {
    name: '',
    surname: '',
    email: '',
    password: ''
  };

  public message: string = '';
  public messageType: 'success' | 'error' | '' = '';

  constructor(private userService: UserService, private router: Router, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    console.log('El componente register-component ha sido cargado');
  }

  async onSubmit() {
    
      try {
        
        this.message = '';
        this.messageType = '';

        const response: any = await firstValueFrom(this.userService.register(this.user));

        if (!response || response.status === 'error') {
          
          switch (response?.msg) {
            case 'Invalid or missing fields !!':
              this.message = 'Por favor, revisa los campos del formulario';
              break;

            case 'User already exists !!':
              this.message = 'Este email ya está registrado';
              break;

            default:
              this.message = 'Error en el registro';
          }

          this.messageType = 'error';
          this.cdr.detectChanges();
          return;
        }

        this.message = 'Registro correcto';
        this.messageType = 'success';
        this.cdr.detectChanges();

        setTimeout(() => {
          this.router.navigate(['/login']);
        }, 2500)

      } catch (error) {
        console.error(error);
        this.message = 'Error inesperado';
        this.messageType = 'error';
      }
    
  }
}

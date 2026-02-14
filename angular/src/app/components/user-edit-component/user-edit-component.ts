import { CommonModule } from '@angular/common';
import { Component, ChangeDetectorRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { UserService } from '../../services/user';
import { Router } from '@angular/router';
import { jwtDecode } from 'jwt-decode';

@Component({
  selector: 'app-user-edit-component',
  imports: [FormsModule, CommonModule],
  templateUrl: './user-edit-component.html',
  styleUrl: './user-edit-component.css',
})
export class UserEditComponent {
public user: {
    name: string;
    surname: string;
    email: string;
    password: string;
  } = {
    name: '',
    surname: '',
    email: '',
    password: ''
  };

  public message = '';
  public messageType: 'success' | 'error' | '' = '';

  constructor(
    private userService: UserService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    const token = this.userService.getToken();

    if (!token) {
      this.message = 'No hay sesión activa. Inicia sesión de nuevo.';
      this.messageType = 'error';
      this.router.navigate(['/login']);
      return;
    }

    // Verifica expiración del token
    try {
      const decoded: any = jwtDecode(token);
      const now = Math.floor(Date.now() / 1000);

      if (decoded.exp < now) {
        this.message = 'Sesión expirada. Inicia sesión de nuevo.';
        this.messageType = 'error';
        this.userService.logout();
        this.router.navigate(['/login']);
        return;
      }
    } catch {
      this.message = 'Token inválido. Inicia sesión de nuevo.';
      this.messageType = 'error';
      this.userService.logout();
      this.router.navigate(['/login']);
      return;
    }
  }

  async onSubmit() {
    this.message = '';
    this.messageType = '';

    const token = this.userService.getToken();
    if (!token) {
      this.message = 'No autorizado. Inicia sesión de nuevo';
      this.messageType = 'error';
      this.cdr.detectChanges();
      return;
    }

    // Construir objeto solo con campos definidos
    const updateData: any = {};
    if (this.user.name) updateData.name = this.user.name;
    if (this.user.surname) updateData.surname = this.user.surname;
    if (this.user.email) updateData.email = this.user.email;
    if (this.user.password) updateData.password = this.user.password;


    try {
      const response: any = await firstValueFrom(
        this.userService.updateUser(updateData)
      );

      if (!response || response.status === 'error') {
        this.message = 'Error al actualizar el usuario';
        this.messageType = 'error';
        this.cdr.detectChanges();
        return;
      }

      // Actualizar identity con los nuevos datos
      this.userService.setIdentity(response.user, token);

      this.message = 'Usuario actualizado correctamente';
      this.messageType = 'success';
      this.cdr.detectChanges();

        // Redirigir a homepage
      setTimeout(() => {
        this.router.navigate(['/']);
      }, 500);

    } catch (error: any) {
      this.message = error.status === 401 
        ? 'No autorizado. Inicia sesión de nuevo'
        : 'Error inesperado';
      this.messageType = 'error';
      this.cdr.detectChanges();
    }
  }
}

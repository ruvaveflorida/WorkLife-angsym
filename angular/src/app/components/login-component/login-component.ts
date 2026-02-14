import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { UserService } from '../../services/user';
import { CommonModule } from '@angular/common';
import { firstValueFrom } from 'rxjs';
import { ActivatedRoute, Router } from '@angular/router';
import { jwtDecode } from 'jwt-decode';


@Component({
  selector: 'login',
  imports: [FormsModule, CommonModule],
  templateUrl: './login-component.html',
  styleUrl: './login-component.css',
  standalone: true,
})
export class LoginComponent implements OnInit {
  public title = 'Componente de login';
  public user: { email: string; password: string; getHash: string | null } = {
    email: '',
    password: '',
    getHash: 'true',
  };

  public identity: any;
  public token: any;

  public message: string = '';
  public messageType: 'success' | 'error' | '' = '';

  constructor(
    private userService: UserService,
    private cdr: ChangeDetectorRef,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit() {
    console.log('El componente login-component ha sido cargado');
    this.logout();
  }

  logout() {
    this.route.paramMap.subscribe(params => {
      const logout = params.get('id');
      if (logout === '1') {
        this.userService.logout();
        this.identity = null;
        this.token = null;
        this.cdr.detectChanges();
        this.router.navigate(['/login']);
      }
    });
  }

async onSubmit() {
  try {
    this.user.getHash = 'true';

    const response: any = await firstValueFrom(
      this.userService.signup(this.user)
    );

    const token = String(response).trim();

    const identity = jwtDecode(token) as any;

    this.userService.setIdentity(identity, token);

    this.identity = identity;
    this.token = token;

    this.message = 'Sesión iniciada correctamente';
    this.messageType = 'success';
    this.cdr.detectChanges();

    setTimeout(() => {
      this.router.navigate(['/']);
    }, 500);

    console.log('Identity guardada:', this.userService.getIdentity());
    console.log('Token guardado:', this.userService.getToken());

  } catch (error: any) {
    console.error('Error en login:', error);
    
    this.message = 'Email o contraseña incorrectos'
    this.messageType = 'error';
    this.cdr.detectChanges();
  }
}
}
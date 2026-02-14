import { Component } from '@angular/core';
import { RouterOutlet, RouterLink, Router } from '@angular/router';
import { NgOptimizedImage, AsyncPipe, CommonModule } from '@angular/common';
import { UserService } from './services/user';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLink, NgOptimizedImage, AsyncPipe, CommonModule],
  templateUrl: './app.html',
  standalone: true,
  styleUrl: './app.css'
})
export class App {

  constructor(public userService: UserService, private router: Router) {
    
  }
logout() {
  this.userService.logout();      
  this.router.navigate(['/login']); 
}
}

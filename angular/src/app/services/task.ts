import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { UrlSymf } from './global';
import { UserService } from './user';

export interface Task {
  id: number;
  title: string;
  description: string;
  status: string;
  createdAt: Date;
  updatedAt: Date;
}

@Injectable({
  providedIn: 'root'
})
export class TaskService {

  public url: string;

  constructor(
    private http: HttpClient,
    private userService: UserService
  ) {
    this.url = UrlSymf.url;
  }

  private getHeaders(): HttpHeaders {
    const token = this.userService.getToken();
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    });
  }
  
  getTasks(page: number): Observable<any> {
    return this.http.post(
      `${this.url}task/list?page=${page}`,
      {}, // body vacío
      {
        headers: this.getHeaders()
      }
    );
  }

  getOneTask(id: number | string): Observable<any> {
  return this.http.post(`${this.url}task/detail/${id}`,{},
     { headers: this.getHeaders() });
  }

  createTask(task: any): Observable<any> {
    return this.http.post(`${this.url}task/new`, task, {
      headers: this.getHeaders()
    });
  }

  updateTask(id: number | string, taskData: any): Observable<any> {
  return this.http.post(`${this.url}task/edit/${id}`, taskData, {
    headers: this.getHeaders()
  });
 }

  searchTasks(search: string, filter: number, order: number, assignedToId: number): Observable<any> {
    const body : any = { filter, order };

    if (assignedToId && assignedToId !== 0) {
     body.assigned_to_id = assignedToId;
    }
    const term = search && search.trim() !== '' ? search : 'all';

    return this.http.post(`${this.url}task/search/${term}`, body, {
      headers: this.getHeaders()
    });
  }

  deleteTask(id: number | string): Observable<any> {
  return this.http.post(`${this.url}task/remove/${id}`, {}, {
    headers: this.getHeaders()
  });
}



}



  


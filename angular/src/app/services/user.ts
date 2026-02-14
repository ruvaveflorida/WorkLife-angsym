import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { UrlSymf } from './global';
import { Observable, BehaviorSubject, throwError } from 'rxjs';
import { map, catchError} from 'rxjs/operators';


@Injectable({
  providedIn: 'root',
})
export class UserService {

  public url: string;
  public identity: any;
  public token: any;

  private identitySubject = new BehaviorSubject<any>(this.getIdentity());
  public identity$ = this.identitySubject.asObservable();

  constructor(private http: HttpClient) {
    this.url = UrlSymf.url;
  }

  private getHeaders(withAuth: boolean = false): HttpHeaders {
  const headersConfig: any = { 'Content-Type': 'application/json' };

  if (withAuth) {
    const token = this.getToken();
    if (!token) {
      throw new Error('No autorizado. Token no disponible');
    }
    headersConfig['Authorization'] = `Bearer ${token}`;
  }

  return new HttpHeaders(headersConfig);
}

  signup(user: any): Observable<any> {
    return this.http.post(`${this.url}login`, user, { headers: this.getHeaders() });
  }

  getIdentity() {
    const identity = localStorage.getItem('identity');

    if (!identity || identity === 'undefined') {
      return null;
    }

    try {
      return JSON.parse(identity);
    } catch {
      return null;
    }
  }

  setIdentity(identity: any, token: string) {
    if (!identity) {
    return;
  }

  localStorage.setItem('identity', JSON.stringify(identity));
  localStorage.setItem('token', token);
  this.identitySubject.next(identity);

  }

  getToken() {
    const token = localStorage.getItem('token');

  if (!token || token === 'undefined') return null;

    return token;
  }

  logout() {
    localStorage.removeItem('identity');
    localStorage.removeItem('token');
    this.identity = null;
    this.token = null;
    this.identitySubject.next(null);
  }

  register(user: any): Observable<any> {
    return this.http.post(`${this.url}user/new`, user, { headers: this.getHeaders() });
  }

  updateUser(user: any): Observable<any> {
    return this.http.post(`${this.url}user/edit`, user, { headers: this.getHeaders(true), observe: 'response' }).pipe(
      map(response => response.body),
      catchError(error => throwError(() => error))
    );
  }


  getAllUsers(): Observable<any> {
  return this.http.get(`${this.url}user/all`, { headers: this.getHeaders(true) });
}
}



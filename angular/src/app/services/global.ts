import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class Global {

}

export const UrlSymf = {
  url: window.location.hostname === 'localhost'
    ? 'http://localhost:8000/api/'
    : 'https://worklife-angsym-production.up.railway.app/'
};

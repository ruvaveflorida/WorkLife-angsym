import { Routes } from '@angular/router';
import { LoginComponent} from './components/login-component/login-component';
import { RegisterComponent} from './components/register-component/register-component';
import { DefaultComponent } from './components/default-component/default-component';
import { UserEditComponent } from './components/user-edit-component/user-edit-component';
import { TaskNewComponent } from './components/task-new-component/task-new-component';
import { TaskDetailComponent } from './components/task-detail-component/task-detail-component';
import { TaskEditComponent } from './components/task-edit-component/task-edit-component';

export const routes: Routes = [
  {path:'', component: DefaultComponent},
  {path:'task/:id', component: TaskDetailComponent},
  {path:'task-edit/:id', component: TaskEditComponent},
  {path:'login', component: LoginComponent},
  {path:'login/:id', component: LoginComponent},
  {path:'register', component: RegisterComponent},
  {path:'user-edit', component: UserEditComponent},
  {path:'task-new', component: TaskNewComponent},
  {path:'**', redirectTo: '', pathMatch: 'full'}
];

import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TaskNewComponent } from './task-new-component';

describe('TaskComponent', () => {
  let component: TaskNewComponent;
  let fixture: ComponentFixture<TaskNewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TaskNewComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TaskNewComponent);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

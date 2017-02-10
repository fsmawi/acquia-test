/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';
import {MaterialModule} from '@angular/material';
import {FormsModule} from '@angular/forms';
import {RouterTestingModule} from '@angular/router/testing';

import { StatusCodeComponent } from './status-code.component';

describe('StatusCodeComponent', () => {
  let component: StatusCodeComponent;
  let fixture: ComponentFixture<StatusCodeComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ StatusCodeComponent ],
      imports: [MaterialModule.forRoot(), FormsModule, RouterTestingModule],
      providers: []
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(StatusCodeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

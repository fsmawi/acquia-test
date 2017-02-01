/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {AuthTokensComponent} from './auth-tokens.component';
import {MaterialModule} from '@angular/material';
import {FormsModule} from '@angular/forms';
import {RouterTestingModule} from '@angular/router/testing';

describe('AuthTokensComponent', () => {
  let component: AuthTokensComponent;
  let fixture: ComponentFixture<AuthTokensComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [AuthTokensComponent],
      imports: [MaterialModule.forRoot(), FormsModule, RouterTestingModule],
      providers: []
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AuthTokensComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

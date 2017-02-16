/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';
import { MockApiComponent } from './mock-api.component';
import {MaterialModule} from '@angular/material';
import {FormsModule} from '@angular/forms';
import {RouterTestingModule} from '@angular/router/testing';
import {environment} from '../../environments/environment';

describe('MockApiComponent', () => {
  let component: MockApiComponent;
  let fixture: ComponentFixture<MockApiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ MockApiComponent ],
      imports: [MaterialModule.forRoot(), FormsModule, RouterTestingModule],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(MockApiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should save new environment header', () => {
    component.headerId = 'someHeaserID';
    component.headerValue = 'someHeaserValue';
    component.save();
    const headers = environment.headers;
    expect(headers['someHeaserID']).toBeDefined();
    expect(headers['someHeaserID']).toEqual('someHeaserValue');
  });
});

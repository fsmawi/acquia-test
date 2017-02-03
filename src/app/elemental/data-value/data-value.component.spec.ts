/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {DataValueComponent} from './data-value.component';

describe('DataValueComponent', () => {
  let component: DataValueComponent;
  let fixture: ComponentFixture<DataValueComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [DataValueComponent]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DataValueComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

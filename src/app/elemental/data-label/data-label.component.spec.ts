/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {DataLabelComponent} from './data-label.component';

describe('DataLabelComponent', () => {
  let component: DataLabelComponent;
  let fixture: ComponentFixture<DataLabelComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [DataLabelComponent]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DataLabelComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

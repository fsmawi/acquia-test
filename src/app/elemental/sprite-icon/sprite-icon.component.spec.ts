/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {SpriteIconComponent} from './sprite-icon.component';

describe('SpriteIconComponent', () => {
  let component: SpriteIconComponent;
  let fixture: ComponentFixture<SpriteIconComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [SpriteIconComponent]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SpriteIconComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

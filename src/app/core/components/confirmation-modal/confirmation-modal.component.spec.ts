/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import {DebugElement, EventEmitter} from '@angular/core';

import { ConfirmationModalComponent } from './confirmation-modal.component';
import {ElementalModule} from '../../../elemental/elemental.module';
import {ConfirmationModalService} from '../../services/confirmation-modal.service';

class MockConfirmationModalService {
  openDialog(title: string, message: string, primaryActionText: string, secondaryActionText = '') {
    return Promise.resolve(true);
  }
}

describe('ConfirmationModalComponent', () => {
  let component: ConfirmationModalComponent;
  let fixture: ComponentFixture<ConfirmationModalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ConfirmationModalComponent],
      providers: [{provide: ConfirmationModalService, useClass: MockConfirmationModalService}],
      imports: [ElementalModule]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ConfirmationModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    component.emitter = new EventEmitter<Boolean>();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should close the modal after confirmation', () => {
    component.confirmed();
    expect(component.modalOpen).toEqual(false);
  });

  it('should close the modal if cancelled', () => {
    component.cancelled();
    expect(component.modalOpen).toEqual(false);
  });

});

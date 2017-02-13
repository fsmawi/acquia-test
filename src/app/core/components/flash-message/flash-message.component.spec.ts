/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';
import { FlashMessageComponent } from './flash-message.component';
import { FlashMessageService } from '../../services/flash-message.service';
import {ElementalModule} from '../../../elemental/elemental.module';

describe('FlashMessageComponent', () => {
  let component: FlashMessageComponent;
  let fixture: ComponentFixture<FlashMessageComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ FlashMessageComponent ],
      providers: [FlashMessageService],
      imports: [ElementalModule]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FlashMessageComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should open dialog for more details', () => {
    component.moreDetails();
    expect(component.flashOpen).toEqual(false);
    expect(component.detailsOpen).toEqual(true);
  });

  it('should close flash message', () => {
    component.closeFlash();
    expect(component.flashOpen).toEqual(false);
  });

  it('should close the more details dialog', () => {
    component.closeDetails();
    expect(component.detailsOpen).toEqual(false);
  });

  it('should empty all attribute', () => {
    component.initMessage();
    expect(component.message).toEqual('');
    expect(component.type).toEqual('');
    expect(component.details).toEqual('');
  });
});

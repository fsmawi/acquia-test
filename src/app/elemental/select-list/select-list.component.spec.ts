import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {EventEmitter} from '@angular/core';
import {FormsModule} from '@angular/forms';

import {SelectListComponent} from './select-list.component';
import {ElementalModule} from '../elemental.module';

describe('SelectListComponent', () => {
  let component: SelectListComponent;
  let fixture: ComponentFixture<SelectListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [],
      imports: [
        FormsModule,
        ElementalModule
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SelectListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should select an option', () => {
    const branch = 'branch_name';

    spyOn(component.selected, 'emit');

    component.selectOption(branch);

    expect(component.selected.emit).toHaveBeenCalledWith(branch);
  });
});

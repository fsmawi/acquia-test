import {Component} from '@angular/core';
import {TestBed, async, ComponentFixture} from '@angular/core/testing';
import {By} from '@angular/platform-browser';

import {DragAndDropDirective} from './drag-and-drop.directive';

@Component({
  selector: 'app-test-component',
  template: '<div appDragAndDrop></div>'
})

class TestComponent {
}

describe('DragAndDropDirective', () => {

  let component: TestComponent;
  let fixture: ComponentFixture<TestComponent>;
  let directiveEl;
  let directiveInstance;
  const evt = {
    preventDefault() {
      return true;
    },
    stopPropagation() {

    },
    dataTransfer : {
      files : []
    }

  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [
        TestComponent,
        DragAndDropDirective
      ]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    directiveEl = fixture.debugElement.query(By.directive(DragAndDropDirective));
    directiveInstance = directiveEl.injector.get(DragAndDropDirective);
  });

  it('should create the element successfully', () => {
    expect(directiveEl).not.toBeNull();
  });

  it('should create the directive instance', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
  });

  it('should set the dragged over flag to true', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();

    directiveInstance.onDragOver(evt);
    expect(directiveInstance.draggedOver).toBe(true);
  });

  it('should set the dragged over flag to false on leave', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();

    directiveInstance.onDragLeave(evt);
    expect(directiveInstance.draggedOver).toBe(false);
  });

  it('should set the dragged over flag to false on drop', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();

    directiveInstance.onDrop(evt);
    expect(directiveInstance.draggedOver).toBe(false);
  });
});

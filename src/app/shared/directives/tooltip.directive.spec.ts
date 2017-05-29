import {Component, ElementRef} from '@angular/core';
import {ComponentFixture, TestBed} from '@angular/core/testing';
import {EventManager, By} from '@angular/platform-browser';

import {TooltipDirective} from './tooltip.directive';
import {TooltipComponent} from '../../core/components/tooltip/tooltip.component';
import {TooltipService} from '../../core/services/tooltip.service';

class MockTooltipService {
  show(text: string, position: string, el: ElementRef) {
    return true;
  }

  hide() {
    return true;
  }
}

@Component({
  selector: 'app-test-component',
  template: '<div appTooltip="Tooltip Text" tooltipPosition="top"></div>'
})

class TestComponent {
}

describe('TooltipDirective', () => {
  let component: TestComponent;
  let fixture: ComponentFixture<TestComponent>;
  let directiveEl;
  let directiveInstance;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [
        TestComponent,
        TooltipDirective,
        TooltipComponent
      ],
      providers: [
        { provide: TooltipService, useClass: MockTooltipService},
        EventManager
      ]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    directiveEl = fixture.debugElement.query(By.directive(TooltipDirective));
    directiveInstance = directiveEl.injector.get(TooltipDirective);
  });

  it('should create the element successfully', () => {
    expect(directiveEl).not.toBeNull();
  });

  it('should create the directive instance', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
  });

  it('should read the directive values', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
    expect(directiveInstance.appTooltip).toEqual('Tooltip Text');
    expect(directiveInstance.tooltipPosition).toEqual('top');
  });

  it('should handle the mouse enter', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
    expect(directiveInstance.onMouseEnter()).toEqual(true);
  });

  it('should handle the mouse leave', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
    expect(directiveInstance.onMouseLeave()).toEqual(true);
  });

  it('should handle the right click event', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
    expect(directiveInstance.onRightClick()).toEqual(true);
  });
});

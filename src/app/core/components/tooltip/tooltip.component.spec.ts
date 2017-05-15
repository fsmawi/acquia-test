import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {ElementRef} from '@angular/core';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';

import {TooltipComponent} from './tooltip.component';
import {TooltipService} from '../../services/tooltip.service';

class MockElementRef implements ElementRef {
  nativeElement = {
    querySelector (selector: string) {
      return {
        offsetHeight : 0,
        offsetWidth: 0
      };
    },
    getBoundingClientRect() {
      return {
        top: 0,
        left: 0,
        width: 0,
        height: 0
      };
    }
  };
}

class MockTooltipService {
  show(text: string, position: string, el: ElementRef) {
    return true;
  }

  hide() {
    return true;
  }
}

describe('TooltipComponent', () => {
  let component: TooltipComponent;
  let fixture: ComponentFixture<TooltipComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [ BrowserAnimationsModule ],
      declarations: [ TooltipComponent ],
      providers: [
        { provide: TooltipService, useClass: MockTooltipService },
        { provide: ElementRef, useClass: MockElementRef }
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TooltipComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should show the tooltip', () => {
    const el: ElementRef = new MockElementRef();
    expect(component).toBeTruthy();
    component.toolTipContainer = new MockElementRef();
    component.show('tooltip-text', 'right', el);
    expect(component.tooltip).toBe('tooltip-text');
    expect(component.position).toBe('right');
    expect(component.showTooltip).toBe(true);
  });

  it('should hide the tooltip', () => {
    expect(component).toBeTruthy();
    component.showTooltip = true;
    component.hide();
    expect(component.showTooltip).toBe(false);
  });

  it('should set the position', () => {
    const el: ElementRef = new MockElementRef();
    expect(component).toBeTruthy();
    component.toolTipContainer = new MockElementRef();
    component.showTooltip = true;
    component.tooltip = 'tooltip-text';
    component.position = 'top';
    component.element = el;
    fixture.detectChanges();
    component.setPosition();
    expect(component.tooltipPosition['transform-origin']).toBe('bottom');
  });

  it('should set the position on window resize', () => {
    const el: ElementRef = new MockElementRef();
    expect(component).toBeTruthy();
    component.toolTipContainer = new MockElementRef();
    component.showTooltip = true;
    component.position = 'right';
    component.element = el;
    fixture.detectChanges();
    component.onWindowResize();
    expect(component.tooltipPosition['transform-origin']).toBe('left');
  });

  it('should set the position on window scroll', () => {
    const el: ElementRef = new MockElementRef();
    expect(component).toBeTruthy();
    component.toolTipContainer = new MockElementRef();
    component.showTooltip = true;
    component.position = 'left';
    component.element = el;
    fixture.detectChanges();
    component.onWindowScroll();
    expect(component.tooltipPosition['transform-origin']).toBe('right');
  });
});

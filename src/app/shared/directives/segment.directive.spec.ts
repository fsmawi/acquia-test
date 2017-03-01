/* tslint:disable:no-unused-variable */

import {TestBed, async, ComponentFixture} from '@angular/core/testing';
import {SegmentDirective} from './segment.directive';
import {ElementRef, Component} from '@angular/core';
import {EventManager, By} from '@angular/platform-browser';
import {SegmentService} from '../../core/services/segment.service';

class MockSegmentService {
  trackEvent(eventIdentifier: string, eventData: Object) {
    return true;
  }
}

@Component({
  selector: 'app-test-component',
  template: '<div appSegmentOn="click" ' +
  'segmentEventIdentifier="EventName" [segmentEventData]="{data : \'DATA\'}"></div>'
})
class TestComponent {
}

describe('SegmentDirective', () => {
  let component: TestComponent;
  let fixture: ComponentFixture<TestComponent>;
  let directiveEl;
  let directiveInstance;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [
        TestComponent,
        SegmentDirective
      ],
      providers: [
        {provide: SegmentService, useClass: MockSegmentService},
        EventManager
      ],
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    directiveEl = fixture.debugElement.query(By.directive(SegmentDirective));
    directiveInstance = directiveEl.injector.get(SegmentDirective);
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
    expect(directiveInstance.appSegmentOn).toEqual('click');
    expect(directiveInstance.segmentEventIdentifier).toEqual('EventName');
    expect(directiveInstance.segmentEventData).toBeTruthy();
  });
});

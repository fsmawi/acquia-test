/* tslint:disable:no-unused-variable */

import {TestBed, async, ComponentFixture} from '@angular/core/testing';
import {ElementRef, Component} from '@angular/core';
import {EventManager, By} from '@angular/platform-browser';

import {TrackDirective} from './track.directive';
import {SegmentService} from '../../core/services/segment.service';
import {LiftService} from '../../core/services/lift.service';

class MockSegmentService {
  trackEvent(eventIdentifier: string, eventData: Object) {
    return true;
  }
}

class MockLiftService {
  captureEvent(eventIdentifier: string, eventData: Object) {
    return true;
  }
}

@Component({
  selector: 'app-test-component',
  template: '<div appTrackOn="click" ' +
  'trackEventName="EventName" [trackEventData]="{data : \'DATA\'}"></div>'
})
class TestComponent {
}

describe('TrackDirective', () => {
  let component: TestComponent;
  let fixture: ComponentFixture<TestComponent>;
  let directiveEl;
  let directiveInstance;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [
        TestComponent,
        TrackDirective
      ],
      providers: [
        { provide: SegmentService, useClass: MockSegmentService },
        { provide: LiftService, useClass: MockLiftService },
        EventManager
      ],
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    directiveEl = fixture.debugElement.query(By.directive(TrackDirective));
    directiveInstance = directiveEl.injector.get(TrackDirective);
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
    expect(directiveInstance.appTrackOn).toEqual('click');
    expect(directiveInstance.trackEventName).toEqual('EventName');
    expect(directiveInstance.trackEventData).toBeTruthy();
  });
});

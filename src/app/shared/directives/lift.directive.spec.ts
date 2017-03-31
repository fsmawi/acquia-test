/* tslint:disable:no-unused-variable */
import {TestBed, async, ComponentFixture} from '@angular/core/testing';
import {Component} from '@angular/core';
import {EventManager, By} from '@angular/platform-browser';

import {LiftDirective} from './lift.directive';
import {LiftService} from '../../core/services/lift.service';


class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

@Component({
  selector: 'app-test-component',
  template: '<div appLiftOn="click" ' +
  'liftEventName="EventName" [liftEventData]="{data : \'DATA\'}"></div>'
})

class TestComponent {
}

describe('LiftDirective', () => {
  let component: TestComponent;
  let fixture: ComponentFixture<TestComponent>;
  let directiveEl;
  let directiveInstance;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [
        TestComponent,
        LiftDirective
      ],
      providers: [
        {provide: LiftService, useClass: MockLiftService},
        EventManager
      ],
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    directiveEl = fixture.debugElement.query(By.directive(LiftDirective));
    directiveInstance = directiveEl.injector.get(LiftDirective);
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
    expect(directiveInstance.appLiftOn).toEqual('click');
    expect(directiveInstance.liftEventName).toEqual('EventName');
    expect(directiveInstance.liftEventData).toBeTruthy();
  });
});

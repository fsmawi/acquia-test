import {async, ComponentFixture, TestBed, inject} from '@angular/core/testing';
import {ObservableMedia} from '@angular/flex-layout';
import {MdProgressSpinnerModule, MdTooltipModule} from '@angular/material';
import {RouterTestingModule} from '@angular/router/testing';

import {TopLevelNavigationComponent} from './top-level-navigation.component';
import {SegmentService} from '../../core/services/segment.service';
import {LiftService} from '../../core/services/lift.service';
import {IframeLinkDirective} from '../directives/iframe-link.directive';
import {LiftDirective} from '../directives/lift.directive';
import {SegmentDirective} from '../directives/segment.directive';
import {TrackDirective} from '../directives/track.directive';
import {ErrorService} from '../../core/services/error.service';
import {ElementalModule} from '../../elemental/elemental.module';

class MockOberservableMedia {
  isActive(screenSize: string) {
    return true;
  }
}

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

describe('TopLevelNavigationComponent', () => {
  let component: TopLevelNavigationComponent;
  let fixture: ComponentFixture<TopLevelNavigationComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        TopLevelNavigationComponent,
        IframeLinkDirective,
        LiftDirective,
        SegmentDirective,
        TrackDirective
      ],
      providers: [
        {provide: LiftService, useClass: MockLiftService},
        {provide: ObservableMedia, useClass: MockOberservableMedia},
        SegmentService,
        ErrorService
      ],
      imports: [
        MdProgressSpinnerModule,
        MdTooltipModule,
        RouterTestingModule,
        ElementalModule
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TopLevelNavigationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should toggle the menu', () => {
    expect(component).toBeTruthy();
    component.showMenu = false;
    component.toggleMenu();
    expect(component.showMenu).toBe(true);

  });

  it('should toggle the popover', () => {
    expect(component).toBeTruthy();
    component.showProfilePopover = false;
    component.toggleProfilePopover();
    expect(component.showProfilePopover).toBe(true);
  });
});

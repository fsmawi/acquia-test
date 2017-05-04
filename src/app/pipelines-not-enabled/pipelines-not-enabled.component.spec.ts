import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {MaterialModule} from '@angular/material';
import {ElementalModule} from '../elemental/elemental.module';
import {RouterTestingModule} from '@angular/router/testing';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';

import {PipelinesNotEnabledComponent} from './pipelines-not-enabled.component';
import {SharedModule} from '../shared/shared.module';
import {SegmentService} from '../core/services/segment.service';
import {LiftService} from '../core/services/lift.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';
import {HelpCenterService} from '../core/services/help-center.service';

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

describe('PipelinesNotEnabledComponent', () => {
  let component: PipelinesNotEnabledComponent;
  let fixture: ComponentFixture<PipelinesNotEnabledComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        MaterialModule.forRoot(),
        ElementalModule,
        RouterTestingModule,
        SharedModule,
        BrowserAnimationsModule
      ],
      declarations: [ PipelinesNotEnabledComponent ],
      providers: [
        SegmentService,
        PipelinesService,
        ErrorService,
        FlashMessageService,
        ConfirmationModalService,
        HelpCenterService,
        { provide: LiftService, useClass: MockLiftService }
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PipelinesNotEnabledComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {MdDialog, MdDialogModule} from '@angular/material';
import {NgModule} from '@angular/core';
import {FormsModule} from '@angular/forms';
import {BrowserDynamicTestingModule} from '@angular/platform-browser-dynamic/testing';
import {FlexLayoutModule} from '@angular/flex-layout';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';

import {EncryptCredentialsComponent} from './encrypt-credentials.component';
import {ErrorService} from '../../core/services/error.service';
import {PipelinesService} from '../../core/services/pipelines.service';
import {TrackDirective} from '../directives/track.directive';
import {DragAndDropDirective} from '../directives/drag-and-drop.directive';
import {ElementalModule} from '../../elemental/elemental.module';
import {RouterTestingModule} from '@angular/router/testing';
import {SegmentService} from '../../core/services/segment.service';
import {LiftService} from '../../core/services/lift.service';

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

class MockPipelinesService {
  getEncryptedValue(appId: string, dataItem: string) {
    return Promise.resolve('encrypted-value');
  }
}

class DialogTestModule {
}

describe('EncryptCredentialsComponent', () => {
  let dialog: MdDialog;
  let component: EncryptCredentialsComponent;

  beforeEach(async(() => {
    global['analyticsMock'] = true;
    global['analytics'] = {
      load: (key: string) => {
        return true;
      },
      page: () => {
        return true;
      },
      track: (eventName: string, eventData: Object) => {
        return 'success';
      }
    };
    TestBed.configureTestingModule({
      declarations: [EncryptCredentialsComponent, TrackDirective, DragAndDropDirective],
      providers: [
        ErrorService,
        SegmentService,
        {provide: LiftService, useClass: MockLiftService},
        {provide: PipelinesService, useClass: MockPipelinesService}
      ],
      imports: [
        MdDialogModule,
        ElementalModule,
        FormsModule,
        FlexLayoutModule,
        BrowserAnimationsModule,
        RouterTestingModule
      ]
    })
      .overrideModule(BrowserDynamicTestingModule, {
        set: {
          entryComponents: [EncryptCredentialsComponent],
        },
      })
      .compileComponents();
  }));

  beforeEach(() => {
    dialog = TestBed.get(MdDialog);
    const dialogRef = dialog.open(EncryptCredentialsComponent);
    component = dialogRef.componentInstance;

  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should check the tab selection', () => {
    expect(component).toBeTruthy();

    const tabs = {selectedTab: {tabTile: 'Environment Variables'}};
    component.onTabSelection(tabs);

    expect(component.isSSHTabSelected).toBe(false);
  });

  it('should return the yaml encrypted string for enviroment variable', () => {
    expect(component).toBeTruthy();

    component.isSSHTabSelected = false;
    component.environmentVariableName = 'VAR_NAME';
    component.encrypt()
      .then((encryptedValueYAMLString) =>
        expect(encryptedValueYAMLString).toBe('VAR_NAME:\n  secure: encrypted-value'));
  });

  it('should return the yaml encrypted string for SSH key', () => {
    expect(component).toBeTruthy();

    component.isSSHTabSelected = true;
    component.encrypt()
      .then((encryptedValueYAMLString) =>
        expect(encryptedValueYAMLString).toBe('write-key:\n  secure: encrypted-value'));
  });
});

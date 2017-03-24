import {NgModule, CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {CommonModule} from '@angular/common';
import {MaterialModule} from '@angular/material';

import {AmplitudeService} from './services/amplitude.service';
import {AnsiService} from './services/ansi.service';
import {BugsnagService} from './services/bugsnag.service';
import {LiftService} from './services/lift.service';
import {SegmentService} from './services/segment.service';

import {AuthGuard} from './services/auth-guard.service';
import {AuthService} from './services/auth.service';
import {ConfirmationModalComponent} from './components/confirmation-modal/confirmation-modal.component';
import {ConfirmationModalService} from './services/confirmation-modal.service';
import {ElementalModule} from '../elemental/elemental.module';
import {ErrorService} from './services/error.service';
import {FlashMessageComponent} from './components/flash-message/flash-message.component';
import {FlashMessageService} from './services/flash-message.service';
import {LocalStorageService} from './services/local-storage.service';
import {WebSocketService} from './services/web-socket.service';
import {N3Service} from './services/n3.service';
import {PipelinesService} from './services/pipelines.service';
import {StorageService} from './services/storage.service';

@NgModule({
  imports: [
    CommonModule, ElementalModule, MaterialModule.forRoot()
  ],
  providers: [
    PipelinesService,
    StorageService,
    N3Service,
    ErrorService,
    AuthGuard,
    AuthService,
    FlashMessageService,
    AmplitudeService,
    AnsiService,
    LiftService,
    BugsnagService,
    ConfirmationModalService,
    LocalStorageService,
    SegmentService,
    WebSocketService
  ],
  declarations: [FlashMessageComponent, ConfirmationModalComponent],
  exports: [FlashMessageComponent, ConfirmationModalComponent],
  entryComponents: [ConfirmationModalComponent, FlashMessageComponent]
})
export class CoreModule {
}

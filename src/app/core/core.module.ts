import {NgModule, CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {CommonModule} from '@angular/common';
import {PipelinesService} from './services/pipelines.service';
import {StorageService} from './services/storage.service';
import {N3Service} from './services/n3.service';
import {ErrorService} from './services/error.service';
import {AuthGuard} from './services/auth-guard.service';
import {AuthService} from './services/auth.service';
import {FlashMessageComponent} from './components/flash-message/flash-message.component';
import {FlashMessageService} from './services/flash-message.service';
import {ElementalModule} from '../elemental/elemental.module';
import {AmplitudeService} from './services/amplitude.service';
import {AnsiService} from './services/ansi.service';
import {LiftService} from './services/lift.service';
import {ConfirmationModalComponent} from './components/confirmation-modal/confirmation-modal.component';
import {ConfirmationModalService} from './services/confirmation-modal.service';
import {MaterialModule} from '@angular/material';
import {LocalStorageService} from './services/local-storage.service';
import {SegmentService} from './services/segment.service';

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
    ConfirmationModalService,
    LocalStorageService,
    SegmentService
  ],
  declarations: [FlashMessageComponent, ConfirmationModalComponent],
  exports: [FlashMessageComponent, ConfirmationModalComponent],
  entryComponents: [ConfirmationModalComponent]
})
export class CoreModule {
}

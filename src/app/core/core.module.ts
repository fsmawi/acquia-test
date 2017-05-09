import {NgModule, CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FlexLayoutModule} from '@angular/flex-layout';
import {FormsModule} from '@angular/forms';

import {MomentModule} from 'angular2-moment';

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
import {HelpCenterService} from './services/help-center.service';
import {HelpCenterComponent} from './components/help-center/help-center.component';
import {HelpContentCategoryFilterPipe } from './components/help-center/help-content-category-filter.pipe';
import {SharedModule} from '../shared/shared.module';
import {ContextMenuComponent} from './components/context-menu/context-menu.component';
import {ContextMenuService} from './services/context-menu.service';
import {TooltipComponent} from './components/tooltip/tooltip.component';
import {TooltipService} from './services/tooltip.service';
import {ApplicationsListComponent} from './components/applications-list/applications-list.component';

@NgModule({
  imports: [
    CommonModule,
    ElementalModule,
    FormsModule,
    SharedModule,
    FlexLayoutModule,
    MomentModule
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
    WebSocketService,
    HelpCenterService,
    ContextMenuService,
    TooltipService
  ],
  declarations: [
    FlashMessageComponent,
    ConfirmationModalComponent,
    HelpCenterComponent,
    HelpContentCategoryFilterPipe,
    ContextMenuComponent,
    TooltipComponent,
    ApplicationsListComponent
  ],
  exports: [
    FlashMessageComponent,
    ConfirmationModalComponent,
    HelpCenterComponent,
    ContextMenuComponent,
    TooltipComponent,
    ApplicationsListComponent
  ],
  entryComponents: [
    ConfirmationModalComponent,
    FlashMessageComponent,
    HelpCenterComponent,
    ContextMenuComponent,
    TooltipComponent,
    ApplicationsListComponent
  ]
})
export class CoreModule {
}

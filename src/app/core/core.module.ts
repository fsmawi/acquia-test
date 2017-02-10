import {NgModule, CUSTOM_ELEMENTS_SCHEMA} from '@angular/core';
import {CommonModule} from '@angular/common';
import {GithubService} from './services/github.service';
import {PipelinesService} from './services/pipelines.service';
import {StorageService} from './services/storage.service';
import {N3Service} from './services/n3.service';
import {ErrorService} from './services/error.service';
import {AuthGuard} from './services/auth-guard.service';
import {AuthService} from './services/auth.service';
import {FlashMessageComponent} from './components/flash-message/flash-message.component';
import { FlashMessageService} from './services/flash-message.service';
import {ElementalModule} from '../elemental/elemental.module';

@NgModule({
  imports: [
    CommonModule, ElementalModule
  ],
  providers: [
    GithubService,
    PipelinesService,
    StorageService,
    N3Service,
    ErrorService,
    AuthGuard,
    AuthService,
    FlashMessageService
  ],
  declarations: [FlashMessageComponent],
  exports: [FlashMessageComponent]
})
export class CoreModule {
}

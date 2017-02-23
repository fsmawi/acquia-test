/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { ConfirmationModalService } from './confirmation-modal.service';
import {EventEmitter} from '@angular/core';

describe('ConfirmationModalService', () => {
  const emitter = new EventEmitter<Boolean>();
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [ConfirmationModalService]
    });
    ConfirmationModalService.prototype.show = function(title: string, message: string,
                                                       primaryActionText: string, secondaryActionText: string) {
      return emitter;
    };
  });

  it('should create the service', inject([ConfirmationModalService], (service: ConfirmationModalService) => {
    expect(service).toBeTruthy();
  }));

  it('should call the show', inject([ConfirmationModalService], (service: ConfirmationModalService) => {
    spyOn(service, 'show');
    service.show('Terminate Job', 'Are you sure you want to terminate your job?', 'Yes', 'Cancel');
    expect(service.show).toHaveBeenCalledWith('Terminate Job', 'Are you sure you want to terminate your job?', 'Yes', 'Cancel');
  }));

});

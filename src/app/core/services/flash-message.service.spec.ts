/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { FlashMessageService } from './flash-message.service';

describe('FlashMessageService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [FlashMessageService]
    });
    FlashMessageService.prototype.show = function(type: string, text: string, details: any) {};
  });

  it('should ...', inject([FlashMessageService], (service: FlashMessageService) => {
    expect(service).toBeTruthy();
  }));

  it('should show an error flash message', inject([FlashMessageService], (service: FlashMessageService) => {
    spyOn(service, 'show');
    service.showError('message', 'more details');
    expect(service.show).toHaveBeenCalledWith('error', 'message', 'more details');
  }));

  it('should show an info flash message', inject([FlashMessageService], (service: FlashMessageService) => {
    spyOn(service, 'show');
    service.showInfo('message', 'more details');
    expect(service.show).toHaveBeenCalledWith('info', 'message', 'more details');
  }));

  it('should show a success flash message', inject([FlashMessageService], (service: FlashMessageService) => {
    spyOn(service, 'show');
    service.showSuccess('message', 'more details');
    expect(service.show).toHaveBeenCalledWith('success', 'message', 'more details');
  }));

  it('should show a warning flash message', inject([FlashMessageService], (service: FlashMessageService) => {
    spyOn(service, 'show');
    service.showWarning('message', 'more details');
    expect(service.show).toHaveBeenCalledWith('warning', 'message', 'more details');
  }));
});

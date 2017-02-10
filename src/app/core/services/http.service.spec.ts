/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { HttpService } from './http.service';
import { HttpModule } from '@angular/http';

describe('HttpService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [HttpService],
      imports: [HttpModule]
    });
  });

  it('should ...', inject([HttpService], (service: HttpService) => {
    expect(service).toBeTruthy();
  }));
});

/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { OauthService } from './oauth.service';
import { HttpModule } from '@angular/http';

describe('OauthService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [OauthService],
      imports: [HttpModule]
    });
  });

  it('should ...', inject([OauthService], (service: OauthService) => {
    expect(service).toBeTruthy();
  }));
});

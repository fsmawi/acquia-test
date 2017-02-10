/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { GithubService } from './github.service';
import { HttpModule } from '@angular/http';

describe('GithubService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [GithubService],
      imports: [HttpModule]
    });
  });

  it('should ...', inject([GithubService], (service: GithubService) => {
    expect(service).toBeTruthy();
  }));
});

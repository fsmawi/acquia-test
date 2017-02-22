/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {AnsiService} from './ansi.service';

describe('AnsiService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [AnsiService]
    });
  });

  it('should build', inject([AnsiService], (service: AnsiService) => {
    expect(service).toBeTruthy();
  }));

  it('should convert ansi text to a html colored output', inject([AnsiService], service => {
    const raw = `\x1B[1;33;40m 33;40`;
    const finished = `<span style="color:rgb(255, 255, 85);background-color:rgb(0, 0, 0)"> 33;40</span>`;
    expect(service.convert(raw)).toEqual(finished);
  }));
});

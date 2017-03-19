/* tslint:disable:no-unused-variable */
import {TestBed} from '@angular/core/testing';

import {LogChunksPipe} from './log-chunks.pipe';

describe('LogChunks Pipe', () => {

  let pipe;

  beforeEach(() => {
    TestBed
      .configureTestingModule({
        declarations: [],
        providers: []
      });
  });

  beforeEach(() => {
    pipe = new LogChunksPipe();
  });

  it('should create the pipe', () => {
    expect(pipe).toBeTruthy();
  });

  it('should return an array of log chunks', () => {
    const chunks = pipe.transform(`Something\nExecuting step install\nsomething else\nExiting step install\npost items`);
    expect(chunks.length).toBe(3);
    expect(chunks[0].log).toContain('Something');
    expect(chunks[1].log).toContain('something else');
    expect(chunks[2].log).toContain('post items');
  });

  it('should return the standard array when no chunks found', () => {
    const chunks = pipe.transform(`Something`);
    expect(chunks.length).toBe(1);
    expect(chunks[0].log).toContain('Something');
  });
});

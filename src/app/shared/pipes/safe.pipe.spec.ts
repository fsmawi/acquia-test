/* tslint:disable:no-unused-variable */
import {TestBed, inject} from '@angular/core/testing';
import {SafePipe} from './safe.pipe';
import {DomSanitizer} from '@angular/platform-browser';

describe('SafePipe', () => {

  let pipe;

  beforeEach(() => {
    TestBed
      .configureTestingModule({
        declarations: [],
        providers: [
          DomSanitizer
        ]
      });
  });

  beforeEach(inject([DomSanitizer], (domSanitizer: DomSanitizer) => {
    pipe = new SafePipe(domSanitizer);
  }));

  it('should create the pipe', () => {
    expect(pipe).toBeTruthy();
  });

  it('should throw exception when invalid type is passed', () => {
    const htmlString = '';
    const type = '';
    try {
      pipe.transform(htmlString, '');
    } catch (e) {
      expect(e).toEqual(Error(`Invalid safe type specified: ${type}`));
    }
  });

  it('should allow html through the pipe', () => {
    const htmlContent = `<input type="text" name="name">`;
    expect(() => pipe.transform(htmlContent, 'html')).not.toThrow();
  });

  it('should allow style through the pipe', () => {
    const htmlContent = `<style></style>`;
    expect(() => pipe.transform(htmlContent, 'style')).not.toThrow();
  });

  it('should allow scripts through the pipe', () => {
    const htmlContent = `<script></script>`;
    expect(() => pipe.transform(htmlContent, 'html')).not.toThrow();
  });

  it('should allow urls through the pipe', () => {
    const htmlContent = `https://google.com`;
    expect(() => pipe.transform(htmlContent, 'url')).not.toThrow();
  });

  it('should allow resource urls through the pipe', () => {
    const htmlContent = `https://google.com`;
    expect(() => pipe.transform(htmlContent, 'resourceUrl')).not.toThrow();
  });
});

import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {LogChunkComponent} from './log-chunk.component';
import {SharedModule} from '../../../shared/shared.module';

describe('LogChunkComponent', () => {
  let component: LogChunkComponent;
  let fixture: ComponentFixture<LogChunkComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        LogChunkComponent
      ],
      imports: [
        SharedModule
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LogChunkComponent);
    component = fixture.componentInstance;
    component.text = 'line1\nline2\nline3\nline4\nline5\nline6\nline7\nline8\nline8';
    fixture.detectChanges();
  });

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('should get specific page', () => {
    component.linesPerPage = 2;
    component.getPage(2);
    expect(component.chunk).toEqual('line3\nline4\n');
  });

  it('should get next page', () => {
    component.currentPage = 1;
    component.nbPages = 4;
    component.nextPage();
    expect(component.currentPage).toEqual(2);
  });

  it('should get previous page', () => {
    component.currentPage = 2;
    component.nbPages = 4;
    component.prevPage();
    expect(component.currentPage).toEqual(1);
  });

  it('should get first page', () => {
    component.currentPage = 3;
    component.nbPages = 4;
    component.firstPage();
    expect(component.currentPage).toEqual(1);
  });

  it('should get last page', () => {
    component.currentPage = 1;
    component.nbPages = 4;
    component.lastPage();
    expect(component.currentPage).toEqual(4);
  });
});

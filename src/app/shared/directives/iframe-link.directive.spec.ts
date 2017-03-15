/* tslint:disable:no-unused-variable */

import {ElementRef, Component} from '@angular/core';
import {EventManager, By} from '@angular/platform-browser';
import {RouterTestingModule} from '@angular/router/testing';
import {TestBed, async, ComponentFixture} from '@angular/core/testing';

import {IframeLinkDirective} from './iframe-link.directive';

@Component({
  selector: 'app-test-component',
  template: '<a appIframeLink="/jobs/123">Test link</a>'
})
class TestComponent {
}

describe('IframeLinkDirective', () => {
  let component: TestComponent;
  let fixture: ComponentFixture<TestComponent>;
  let directiveEl;
  let directiveInstance;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [
        TestComponent,
        IframeLinkDirective
      ],
      imports: [RouterTestingModule]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    directiveEl = fixture.debugElement.query(By.directive(IframeLinkDirective));
    directiveInstance = directiveEl.injector.get(IframeLinkDirective);
  });

  it('should create the element successfully', () => {
    expect(directiveEl).not.toBeNull();
  });

  it('should create the directive instance', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
  });

  it('should read the directive values', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
    expect(directiveInstance.appIframeLink).toEqual('/jobs/123');
  });
});

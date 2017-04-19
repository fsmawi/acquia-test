import {ElementRef, Component} from '@angular/core';
import {EventManager, By} from '@angular/platform-browser';
import {RouterTestingModule} from '@angular/router/testing';
import {TestBed, async, ComponentFixture} from '@angular/core/testing';

import {ContextLinkDirective} from './context-link.directive';
import {ContextMenuService} from '../../core/services/context-menu.service';

@Component({
  selector: 'app-test-component',
  template: '<a [appContextLink]="[{text: \'text link\', link: \'url\', target: \'_blank\'}]">Test link</a>'
})
class TestComponent {
}

describe('ContextLinkDirective', () => {
  let component: TestComponent;
  let fixture: ComponentFixture<TestComponent>;
  let directiveEl;
  let directiveInstance;

  beforeEach(() => {
    TestBed.configureTestingModule({
      declarations: [
        TestComponent,
        ContextLinkDirective
      ],
      providers: [ContextMenuService],
      imports: [RouterTestingModule]
    })
      .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    directiveEl = fixture.debugElement.query(By.directive(ContextLinkDirective));
    directiveInstance = directiveEl.injector.get(ContextLinkDirective);
  });

  it('should create the element successfully', () => {
    expect(directiveEl).not.toBeNull();
  });

  it('should create an instance', () => {
    expect(directiveEl).not.toBeNull();
    expect(directiveInstance).toBeTruthy();
  });

  it('should read the directive values', () => {
    expect(directiveInstance.appContextLink[0].text).toEqual('text link');
    expect(directiveInstance.appContextLink[0].link).toEqual('url');
  });
});

/* tslint:disable:no-unused-variable */

import {TestBed, async} from '@angular/core/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {AppComponent} from './app.component';
import {CoreModule} from './core/core.module';

describe('AppComponent', () => {
  beforeEach(() => {
    global['ampMock'] = {
      getInstance: () => {
        return {
          init: () => {
          },
          logEvent: () => {
          }
        };
      }
    };
    TestBed.configureTestingModule({
      imports: [
        RouterTestingModule, CoreModule
      ],
      declarations: [
        AppComponent
      ],
    });
    TestBed.compileComponents();
  });

  it('should create the app', async(() => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.debugElement.componentInstance;
    expect(app).toBeTruthy();
  }));
});

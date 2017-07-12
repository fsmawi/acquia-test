import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {ElementRef} from '@angular/core';

import {RepoTypeIconComponent} from './repo-type-icon.component';
import {ElementalModule} from '../../elemental/elemental.module';
import {TooltipDirective} from '../directives/tooltip.directive';
import {TooltipService} from '../../core/services/tooltip.service';

class MockTooltipService {
  show(text: string, position: string, el: ElementRef) {
    return true;
  }

  hide() {
    return true;
  }
}

describe('RepoTypeIconComponent', () => {
  let component: RepoTypeIconComponent;
  let fixture: ComponentFixture<RepoTypeIconComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        RepoTypeIconComponent,
        TooltipDirective
      ],
      providers: [
        {provide: TooltipService, useClass: MockTooltipService},
      ],
      imports: [ ElementalModule ],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(RepoTypeIconComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

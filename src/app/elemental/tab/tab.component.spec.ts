import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {TabComponent} from './tab.component';

describe('TabComponent', () => {
  let component: TabComponent;
  let fixture: ComponentFixture<TabComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TabComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TabComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should activate Tabe', () => {
    component.activate();
    expect(component.active).toEqual(true);
  });

  it('should deactivate Tabe', () => {
    component.deactivate();
    expect(component.active).toEqual(false);
  });
});

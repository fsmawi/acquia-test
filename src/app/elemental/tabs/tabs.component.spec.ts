import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {EventEmitter} from '@angular/core';
import {FlexLayoutModule} from '@angular/flex-layout';

import {TabsComponent} from './tabs.component';
import {TabComponent} from '../tab/tab.component';

describe('TabsComponent', () => {
  let component: TabsComponent;
  let fixture: ComponentFixture<TabsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        TabsComponent,
        TabComponent
      ],
      imports: [FlexLayoutModule]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TabsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should select Tab', () => {
    const tab = new TabComponent();

    spyOn(component.selected, 'emit');

    component.selectTab(tab);

    expect(component.selected.emit).toHaveBeenCalled();
  });
});

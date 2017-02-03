import {Component, OnInit, Input, HostBinding} from '@angular/core';

@Component({
  selector: 'e-svg-icon',
  templateUrl: './svg-icon.component.html',
  styleUrls: ['./svg-icon.component.scss']
})
export class SvgIconComponent implements OnInit {

  @HostBinding('class.el-iconography') icon = true;
  @HostBinding('class.el-iconography__icon--is-gargantuan') isGargantuan = false;
  @HostBinding('class.el-iconography__icon--is-giant') isGiant = false;
  @HostBinding('class.el-iconography__icon--is-large') isLarge = false;
  @HostBinding('class.el-iconography__icon--is-medium') isMedium = false;
  @HostBinding('class.el-iconography__icon--is-small') isSmall = false;

  @Input()
  type: string;

  @Input()
  size = 'medium';

  @Input()
  label: string;

  constructor() {
  }

  ngOnInit() {
    this['is' + this.size.replace(/\b(\w)/g, s => s.toUpperCase())] = true;
  }
}

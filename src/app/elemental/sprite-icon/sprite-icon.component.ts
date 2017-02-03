import {Component, OnInit, Input} from '@angular/core';

@Component({
  selector: 'e-sprite-icon',
  templateUrl: './sprite-icon.component.html',
  styleUrls: ['./sprite-icon.component.scss']
})
export class SpriteIconComponent implements OnInit {

  @Input()
  type: string;

  constructor() {
  }

  ngOnInit() {
  }

}

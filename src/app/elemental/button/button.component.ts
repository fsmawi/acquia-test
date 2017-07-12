import {Component, OnInit, Input} from '@angular/core';

@Component({
  selector: 'e-button',
  templateUrl: './button.component.html',
  styleUrls: ['./button.component.scss']
})
export class ButtonComponent implements OnInit {

  @Input()
  text: string;

  @Input()
  type = 'primary';

  @Input()
  disabled: boolean;

  constructor() {
  }

  ngOnInit() {
  }
}

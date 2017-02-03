import {Component, OnInit, Input} from '@angular/core';

@Component({
  selector: 'e-alert',
  templateUrl: './alert.component.html',
  styleUrls: ['./alert.component.scss']
})
export class AlertComponent implements OnInit {

  @Input()
  icon = true;

  @Input()
  type = 'success';

  constructor() {
  }

  ngOnInit() {
  }

}

import {trigger, state, style, transition, animate} from '@angular/core';

export const animations = [
  trigger('fadeInUp', [
    state('in', style({
      transform: 'translate(0, 0)',
      opacity: 1,
    })),
    transition(':enter', [
      style({
        transform: 'translate(0, 10px)',
        opacity: 0,
      }),
      animate(500)
    ])
  ])
];

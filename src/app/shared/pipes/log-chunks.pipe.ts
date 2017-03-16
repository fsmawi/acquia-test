import {Pipe, PipeTransform} from '@angular/core';

@Pipe({name: 'logChunks'})
export class LogChunksPipe implements PipeTransform {
  transform(message: string) {
    // start with messages
    // regex the messages
    // return each of those chunks in an obj/arr
    // else for if doesn't exist

    // Is the full log, only returned when empty
    const initLog = message;

    const rawChunks = initLog.match(/^(Executing step .+)[\s\S]+?(^Exiting step .+|Killing background jobs)/gm);

    if (!rawChunks) {
      return [{
        title: initLog,
        log: initLog
      }];
    } else {
      const preface = initLog.substring(0, initLog.indexOf(rawChunks[0]));
      const postscript = initLog.split(rawChunks[rawChunks.length - 1])[1];

      let chunks = [{
        title: 'Initialization',
        log: preface
      }];

      chunks = chunks.concat(
        rawChunks.map(
          c => {
            return {
              title: c.split('\n')[0],
              log: c
            };
          }
        )
      );

      chunks.push({
        title: 'Finalization',
        log: postscript
      });

      return chunks;
    }
  }
}

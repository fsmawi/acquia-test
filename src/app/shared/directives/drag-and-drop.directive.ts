import {Directive, HostListener, HostBinding, EventEmitter, Output, Input} from '@angular/core';

@Directive({
  selector: '[appDragAndDrop]'
})
export class DragAndDropDirective {

  /***
   * Check if the file is dragged over
   * @type {boolean}
   */
  @HostBinding('class.drag-over') draggedOver = false;

  /**
   * Emit the when the new files are dragged
   * @type {EventEmitter}
   */
  @Output()
  filesChangeEmiter: EventEmitter<FileList> = new EventEmitter();

  /**
   * Holds the allowed extensions to drag
   * @type {Array}
   */
  @Input()
  allowed_extensions: Array<string> = [];

  /**
   * Builds the directive
   */
  constructor() { }

  /**
   * Listens to the drag over event
   * @param evt
   */
  @HostListener('dragover', ['$event']) onDragOver(evt) {
    evt.preventDefault();
    evt.stopPropagation();
    this.draggedOver = true;
  }

  /**
   * Listens to the drag leave event
   * @param evt
   */
  @HostListener('dragleave', ['$event']) onDragLeave(evt) {
    evt.preventDefault();
    evt.stopPropagation();
    this.draggedOver = false;
  }

  /**
   * Listens to the drop event
   * @param evt
   */
  @HostListener('drop', ['$event']) onDrop(evt) {
    evt.preventDefault();
    evt.stopPropagation();
    const files = evt.dataTransfer.files;
    const validFiles: Array<File> = [];
    this.draggedOver = false;
    if (files.length > 0) {
      if (this.allowed_extensions.length > 0) {
        for (let i = 0; i < files.length; i++) {
          const file = files[0];
          const ext = file.name.split('.')[file.name.split('.').length - 1];
          if (this.allowed_extensions.lastIndexOf(ext) !== -1) {
            validFiles.push(file);
          }
        }
        if (validFiles.length > 0) {
          const validFilesConvertedToFilesList: any = validFiles;
          this.filesChangeEmiter.emit(validFilesConvertedToFilesList);
        }

      } else {
        this.filesChangeEmiter.emit(files);
      }
    }
  }
}

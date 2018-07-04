<tr>
    <td><a class="collapsed row-button" data-toggle="collapse" href="#row-{{ $device->iddevices }}" role="button" aria-expanded="false" aria-controls="row-1">Edit <span class="arrow">▴</span></a></td>
    <td class="text-center">0</td>
    <td>{{ $device->deviceCategory->name }}</td>
    <td>{{ $device->brand }}</td>
    <td>{{ $device->model }}</td>
    <td>{{ $device->age }}</td>
    <td>{!! $device->problem !!}</td>
    @if ( $device->repair_status == 1 )
      <td><span class="badge badge-success">Fixed</span></td>
    @elseif ( $device->repair_status == 2 )
      <td><span class="badge badge-warning">Repairable</span></td>
    @else
      <td><span class="badge badge-danger">End</span></td>
    @endif
    @if ($device->more_time_needed == 1)
      <td>More time needed</td>
    @elseif ($device->professional_help == 1)
      <td>Professional help</td>
    @elseif ($device->do_it_yourself == 1)
      <td>Do it yourself</td>
    @else
      <td>N/A</td>
    @endif
    <td class="text-center">
      @if ($device->spare_parts == 1)
        <svg class="table-tick" width="21" height="17" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;position:relative;z-index:1"><g><path d="M5.866,12.648l2.932,-2.933l-5.865,-5.866l-2.933,2.933l5.866,5.866Z" style="fill:#0394a6;"/><path d="M15.581,2.933l-2.933,-2.933l-9.715,9.715l2.933,2.933l9.715,-9.715Z" style="fill:#0394a6;"/></g></svg>
      @endif
    </td>
    <td><a class="collapsed row-button" data-toggle="collapse" href="#row-{{ $device->iddevices }}" role="button" aria-expanded="false" aria-controls="row-1" data-toggle="popover" data-content="Delete device" data-trigger="hover"><svg width="15" height="15" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg></a></td>
</tr>
<tr class="collapse table-row-details" id="row-{{ $device->iddevices }}">
    <td colspan="11">
        <form id="data-{{ $device->iddevices }}" class="edit-device" data-device="{{ $device->iddevices }}" method="post" enctype="multipart/form-data">
        <table class="table">
            <tbody>
                <tr>
                    <td>
                        <label for="nested-5">Brand:</label>
                        <div class="form-control form-control__select">
                            <select name="brand-{{ $device->iddevices }}" id="brand-{{ $device->iddevices }}">
                                @foreach($brands as $brand)
                                  @if ($device->brand == $brand->brand_name)
                                    <option value="{{ $brand->id }}" selected>{{ $brand->brand_name }}</option>
                                  @else
                                    <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                                  @endif
                                @endforeach
                            </select>
                        </div>
                    </td>
                    <td>
                        <label for="nested-6">Model:</label>
                        <div class="form-group">
                            <input type="text" class="form-control field" id="model-{{ $device->iddevices }}" name="model-{{ $device->iddevices }}" value="{{ $device->model }}">
                        </div>
                    </td>
                    <td>
                        <label for="nested-7">Age:</label>
                        <div class="form-group">
                            <input type="text" class="form-control field" id="age-{{ $device->iddevices }}" name="age-{{ $device->iddevices }}" value="{{ $device->age }}">
                        </div>
                    </td>
                    <td>
                        <label for="status-{{ $device->iddevices }}">Status:</label>
                        <div class="form-control form-control__select">
                            <select class="checkStatus" name="status" id="status-{{ $device->iddevices }}" data-device="{{ $device->iddevices }}">
                              @if ( $device->repair_status == 1 )
                                <option value="1" selected>Fixed</option>
                                <option value="2">Repairable</option>
                                <option value="3">End of Life</option>
                              @elseif ( $device->repair_status == 2 )
                                <option value="1">Fixed</option>
                                <option value="2" selected>Repairable</option>
                                <option value="3">End of Life</option>
                              @else
                                <option value="1">Fixed</option>
                                <option value="2">Repairable</option>
                                <option value="3" selected>End of Life</option>
                              @endif
                            </select>
                        </div>
                    </td>
                    <td>
                        <label for="repair-info-{{ $device->iddevices }}" class="sr-only">Repairable: more information</label>
                        <div class="form-control form-control__select">
                            <select name="repair-info" id="repair-info-{{ $device->iddevices }}" disabled>
                              <option value="0">Repair Details</option>
                              @if ( $device->more_time_needed == 1 )
                                <option value="1" selected>More time needed</option>
                                <option value="2">Professional help</option>
                                <option value="3">Do it yourself</option>
                              @elseif ( $device->professional_help == 1 )
                                <option value="1">More time needed</option>
                                <option value="2" selected>Professional help</option>
                                <option value="3">Do it yourself</option>
                              @elseif ( $device->do_it_yourself == 1 )
                                <option value="1" >More time needed</option>
                                <option value="2">Professional help</option>
                                <option value="3" selected>Do it yourself</option>
                              @else
                                <option value="1">More time needed</option>
                                <option value="2">Professional help</option>
                                <option value="3">Do it yourself</option>
                              @endif
                            </select>
                        </div>
                    </td>
                    <td>
                        <label for="spare_parts">Spare parts:</label>
                        <div class="form-control form-control__select">
                            <select name="spare-parts-{{ $device->iddevices }}" id="spare-parts-{{ $device->iddevices }}">
                              @if ($device->spare_parts == 1)
                                <option value="1" selected>Yes</option>
                                <option value="2">No</option>
                              @else
                                <option value="1">Yes</option>
                                <option value="2" selected>No</option>
                              @endif
                            </select>
                        </div>
                    </td>
                </tr>
                <tr class="table-row-more">
                    <td colspan="5">
                        <label for="description">Description of problem/solution:</label>
                        <div class="form-group">
                            <textarea name="problem">{!! $device->problem !!}</textarea>
                        </div>
                    </td>
                    <td colspan="2" class="table-cell-upload-td">
                        <div class="table-cell-upload">
                            <div class="form-group">
                                <label for="file">Add image:</label>

                                <form id="dropzoneEl" class="dropzone" action="/device/image-upload/{{ $device->iddevices }}" method="post" enctype="multipart/form-data" data-field1="Add device images here" data-field2="Choose compelling images that show off your work">
                                    <div class="fallback" >
                                        <input id="file-{{ $device->iddevices }}" name="file-{{ $device->iddevices }}" type="file" multiple />
                                    </div>
                                </form>

                                <div class="previews"></div>

                            </div>

                            <div class="row">
                                <div class="col-9 d-flex align-content-center flex-column">
                                    <div class="form-check d-flex align-items-center justify-content-start">
                                        <input class="form-check-input" type="checkbox" name="wiki-{{ $device->iddevices }}" id="wiki-{{ $device->iddevices }}" value="1">
                                        <label class="form-check-label" for="wiki-{{ $device->iddevices }}">Could the solution comments help Restarters working on a similar device in future?  Or is it a fun case study?</label>
                                    </div>
                                </div>
                                <div class="col-3 d-flex justify-content-end flex-column"><div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary btn-save2">Save</button></div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        </form>
    </td>
</tr>
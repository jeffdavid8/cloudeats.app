<?
switch ($style) {
  case 'context-menu':
?>
    <button href="#" style="margin: 0 20px; background-color: rgba(111, 111, 111, 0.3);" class="new-stitch-form-dropdown-trigger btn-round-action hide-on-med-and-up" data-target="new-stitch-form-context-menu">
      <i style="font-size: 2rem;" class="material-icons">more_vert</i>
    </button>

    <!-- The Dropdown Structure -->
    <ul id="new-stitch-form-context-menu" class="dropdown-content" style="">
      <li class="tab">
        <a data-target="#text-editor" class="active waves-effect waves-light">
          <i class="fas fa-code grey-text"></i> <label>CONTENT</label>
        </a>
      </li>
      <li class="tab">
        <a data-target="#new-stitch-form-data-type" class="waves-effect waves-light">
          <i class="fas fa-file-alt grey-text"></i> <label>TYPE</label>
        </a>
      </li>
      <li class="tab">
        <a data-target="#nexus-preview-area" class="waves-effect waves-light">
          <i class="material-icons grey-text">explore</i> <label>CONNECTIONS</label>
        </a>
      </li>
      <li class="tab">
        <a data-target="#new-stitch-form-projected-date" class="waves-effect waves-light">
          <i class="material-icons grey-text">update</i> <label>TIME</label>
        </a>
      </li>
      <li class="tab">
        <a data-target="#new-stitch-form-location" class="waves-effect waves-light">
          <i class="material-icons grey-text">location_on</i> <label>LOCATION</label>
        </a>
      </li>
      <li class="tab">
        <a data-target="#new-stitch-form-visibility" class="waves-effect waves-light">
          <i class="material-icons grey-text">lock</i> <label>PRIVACY</label>
        </a>
      </li>
    </ul>
  <?
    break;

  case 'inline':
  ?>
    <ul class="tab-buttons hide-on-small-only" style="display: flex; justify-content: center; align-items: center;">
      <li class="tab">
        <button data-target="#text-editor" class="active waves-effect waves-light">
          <i class="fas fa-code grey-text"></i> <label>CONTENT</label>
        </button>
      </li>
      <li class="tab">
        <button data-target="#new-stitch-form-data-type" class="waves-effect waves-light">
          <i class="fas fa-file-alt grey-text"></i> <label>TYPE</label>
        </button>
      </li>
      <li class="tab">
        <button data-target="#nexus-preview-area" class="waves-effect waves-light">
          <i class="material-icons grey-text">explore</i> <label>CONNECTIONS</label>
        </button>
      </li>
      <li class="tab">
        <button data-target="#new-stitch-form-projected-date" class="waves-effect waves-light">
          <i class="material-icons grey-text">update</i> <label>TIME</label>
        </button>
      </li>
      <li class="tab">
        <button data-target="#new-stitch-form-location" class="waves-effect waves-light">
          <i class="material-icons grey-text">location_on</i> <label>LOCATION</label>
        </button>
      </li>
      <li class="tab">
        <button data-target="#new-stitch-form-visibility" class="waves-effect waves-light">
          <i class="material-icons grey-text">lock</i> <label>PRIVACY</label>
        </button>
      </li>
      <? /*
      <li class="tab">
        <a href="#!" onclick="toggleStitchForm(); return false;" class="modal-close waves-effect waves-green btn-flat white-text">Cancel</a>
      </li>
      */ ?>
    </ul>
<?
    break;
}
?>
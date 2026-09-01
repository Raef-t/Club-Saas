<?php

namespace Modules\Authentication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\PersonContact;
use Modules\Authentication\Http\Requests\StorePersonContactRequest;
use Modules\Authentication\Http\Requests\UpdatePersonContactRequest;
use Modules\Authentication\Http\Resources\PersonContactResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class PersonContactController extends BaseController
{
    #[OA\Get(
        path: "/v1/contacts",
        operationId: "getPersonContacts",
        summary: "Get all person contacts",
        tags: ["Person Contacts"],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "person_id", in: "query", required: false, description: "Filter contacts by Person ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", required: false, description: "عدد العناصر في الصفحة (أو all لجلب الكل بدون ترقيم)", schema: new OA\Schema(type: "string", example: "15")),
            new OA\Parameter(name: "page", in: "query", required: false, description: "رقم الصفحة", schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = PersonContact::query();
        if ($request->has('person_id')) {
            $query->where('person_id', $request->query('person_id'));
        }

        if ($request->input('per_page') === 'all' || $request->boolean('all') || $request->input('paginate') === 'false') {
            $contacts = $query->get();
        } else {
            $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
            $contacts = $query->paginate($perPage);
        }

        return $this->successResponse(PersonContactResource::collection($contacts), 'Contacts retrieved successfully');
    }

    #[OA\Post(
        path: "/v1/contacts",
        operationId: "storePersonContact",
        summary: "Add a new contact",
        tags: ["Person Contacts"],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/StorePersonContactRequest")
        ),
        responses: [
            new OA\Response(response: 201, description: "Contact created successfully")
        ]
    )]
    public function store(StorePersonContactRequest $request): JsonResponse
    {
        $contact = PersonContact::create($request->validated());
        return $this->successResponse(new PersonContactResource($contact), 'Contact created successfully', 201);
    }

    #[OA\Get(
        path: "/v1/contacts/{contact}",
        operationId: "getPersonContact",
        summary: "Get a specific person contact",
        tags: ["Person Contacts"],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "contact", in: "path", required: true, description: "Contact ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Contact not found")
        ]
    )]
    public function show(PersonContact $contact): JsonResponse
    {
        return $this->successResponse(new PersonContactResource($contact), 'Contact retrieved successfully');
    }

    #[OA\Put(
        path: "/v1/contacts/{contact}",
        operationId: "updatePersonContact",
        summary: "Update an existing person contact",
        tags: ["Person Contacts"],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "contact", in: "path", required: true, description: "Contact ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdatePersonContactRequest")
        ),
        responses: [
            new OA\Response(response: 200, description: "Contact updated successfully"),
            new OA\Response(response: 404, description: "Contact not found")
        ]
    )]
    public function update(UpdatePersonContactRequest $request, PersonContact $contact): JsonResponse
    {
        $contact->update($request->validated());
        return $this->successResponse(new PersonContactResource($contact), 'Contact updated successfully');
    }

    #[OA\Delete(
        path: "/v1/contacts/{contact}",
        operationId: "deletePersonContact",
        summary: "Delete a person contact",
        tags: ["Person Contacts"],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "contact", in: "path", required: true, description: "Contact ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Contact deleted successfully"),
            new OA\Response(response: 404, description: "Contact not found")
        ]
    )]
    public function destroy(PersonContact $contact): JsonResponse
    {
        $contact->delete();
        return $this->successResponse(null, 'Contact deleted successfully');
    }
}
